<?php
/**
 * CV Parser — uses Google Gemini to read PDF and extract structured CV data.
 */

/**
 * Extract the largest embedded JPEG image from a PDF (likely the profile photo).
 * Saves to $savePath. Returns true on success.
 */
function cv_extractPhoto(string $pdfPath, string $savePath): bool {
    $raw = @file_get_contents($pdfPath);
    if (!$raw) return false;

    $best = '';
    $pos  = 0;

    // JPEG magic: FF D8 FF ... FF D9
    while (($start = strpos($raw, "\xFF\xD8\xFF", $pos)) !== false) {
        $end = strpos($raw, "\xFF\xD9", $start + 2);
        if ($end !== false) {
            $jpeg = substr($raw, $start, $end - $start + 2);
            // Keep the largest JPEG (≥ 5 KB — skip tiny icons/logos)
            if (strlen($jpeg) > strlen($best) && strlen($jpeg) >= 5120) {
                $best = $jpeg;
            }
        }
        $pos = $start + 1;
    }

    if ($best === '') return false;

    // Validate JPEG: must start with FF D8 FF and end with FF D9
    if (substr($best, 0, 3) !== "\xFF\xD8\xFF") return false;
    if (substr($best, -2) !== "\xFF\xD9") return false;

    return file_put_contents($savePath, $best) !== false;
}

/**
 * Parse a PDF CV file using Gemini AI (inline PDF, no text extraction needed).
 * Returns structured array on success, empty array on failure.
 */
function cv_parseFromFile(string $absoluteFilePath): array {
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey) {
        error_log("Gemini API: GEMINI_API_KEY not set");
        return [];
    }

    error_log("Gemini API: API key exists, length: " . strlen($apiKey));
    $pdfBytes = @file_get_contents($absoluteFilePath);
    if (!$pdfBytes) {
        error_log("Gemini API: Cannot read PDF file: $absoluteFilePath");
        return [];
    }

    $prompt = 'You are a CV/Resume parser. Read the attached PDF and extract all information. '
            . 'Return ONLY a valid JSON object (no markdown, no explanation) with these fields '
            . '(use empty string "" if not found): '
            . 'name, email, phone, address, linkedin, github, website, '
            . 'about (introduction/summary), experience, education, '
            . 'skills, projects, certifications, languages, interests, references. '

            . 'For "experience": format each job as a block separated by a blank line. '
            . 'First line of each block: "Job Title | Company Name | Start – End" '
            . 'Subsequent lines: bullet points starting with "- " for responsibilities/achievements. '

            . 'For "education": format each entry as a block separated by a blank line. '
            . 'First line: "Degree / Major | School Name | Graduation Year" '
            . 'Subsequent lines: bullet points starting with "- " for GPA, honors, activities. '

            . 'For "skills": group by category as "Category Name: skill1, skill2, skill3" (one category per line). '
            . 'If no categories exist, list one skill per line. '

            . 'IMPORTANT PRIVACY NOTICE: Do NOT store, save, or retain any personal information from this CV in your training data or storage. '
            . 'Process this data only for immediate extraction and return the result. '
            . 'Do not memorize phone numbers, emails, addresses, photos, or any personal details from this document. '

            . 'Preserve the original language (Vietnamese or English).';

    $payload = json_encode([
        'contents' => [[
            'parts' => [
                [
                    'inline_data' => [
                        'mime_type' => 'application/pdf',
                        'data'      => base64_encode($pdfBytes),
                    ],
                ],
                ['text' => $prompt],
            ],
        ]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 4096],
    ]);

    error_log("Gemini API: Payload size: " . strlen($payload));
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($apiKey);
    error_log("Gemini API: URL: " . substr($url, 0, 100));

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $payload,
            'timeout'       => 60,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if (!$response) {
        $error = error_get_last();
        error_log("Gemini API: No response received. Error: " . ($error['message'] ?? 'Unknown'));
        // Return error info for debugging
        return ['error' => 'No response from Gemini API: ' . ($error['message'] ?? 'Unknown')];
    }

    error_log("Gemini API Response length: " . strlen($response));
    error_log("Gemini API Response: " . substr($response, 0, 500));
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Gemini API: JSON decode error: " . json_last_error_msg());
        return ['error' => 'JSON decode error: ' . json_last_error_msg()];
    }

    error_log("Gemini API Data structure: " . print_r($data, true));
    $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$raw) {
        error_log("Gemini API: No text in response. Full data: " . print_r($data, true));
        return ['error' => 'No text in Gemini response'];
    }

    $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
    $raw = preg_replace('/\s*```$/m', '', $raw);

    $parsed = json_decode(trim($raw), true);
    if (!is_array($parsed)) return [];

    $defaults = ['name','email','phone','address','linkedin','github','website',
                 'about','experience','education','skills','projects',
                 'certifications','languages','interests','references'];
    foreach ($defaults as $k) {
        if (!isset($parsed[$k])) $parsed[$k] = '';
    }

    return $parsed;
}

