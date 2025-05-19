<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$url = $_POST['url'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

if (strpos($url, 'instagram.com') === false) {
    echo json_encode(['error' => 'Not an Instagram URL']);
    exit;
}

// Function to fetch content with proper headers
function fetchWithHeaders($url) {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Connection: keep-alive'
            ])
        ]
    ];
    
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

try {
    // First try to get the HTML
    $html = fetchWithHeaders($url);
    
    if (!$html) {
        echo json_encode(['error' => 'Could not fetch content from Instagram']);
        exit;
    }
    
    // Check for private content
    if (strpos($html, 'login') !== false || strpos($html, 'private') !== false) {
        echo json_encode(['error' => 'This content may be private or require login']);
        exit;
    }
    
    // Try to find video URL
    if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
        $videoUrl = stripslashes($matches[1]);
        echo json_encode([
            'success' => true,
            'type' => 'video',
            'url' => $videoUrl
        ]);
        exit;
    }
    
    // Try to find image URL
    if (preg_match('/property="og:image" content="([^"]+)"/', $html, $matches)) {
        echo json_encode([
            'success' => true,
            'type' => 'image',
            'url' => $matches[1]
        ]);
        exit;
    }
    
    // Alternative method for some content
    if (preg_match('/display_url":"([^"]+)"/', $html, $matches)) {
        $imageUrl = stripslashes($matches[1]);
        echo json_encode([
            'success' => true,
            'type' => 'image',
            'url' => $imageUrl
        ]);
        exit;
    }
    
    echo json_encode(['error' => 'Could not find media in this post']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
