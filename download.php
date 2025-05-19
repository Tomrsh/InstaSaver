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

// Function to fetch Instagram content
function fetchInstagramContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);
    
    return $html;
}

try {
    $html = fetchInstagramContent($url);
    
    // Check if content is private or requires login
    if (strpos($html, 'login') !== false || strpos($html, 'private') !== false) {
        echo json_encode(['error' => 'This content may be private or require login']);
        exit;
    }
    
    // Try to find video URL first
    preg_match('/"video_url":"([^"]+)"/', $html, $videoMatches);
    if (isset($videoMatches[1])) {
        $videoUrl = str_replace('\\', '', $videoMatches[1]);
        echo json_encode([
            'success' => true,
            'type' => 'video',
            'url' => $videoUrl,
            'download_url' => 'download.php?url=' . urlencode($videoUrl)
        ]);
        exit;
    }
    
    // Try to find image URL
    preg_match('/property="og:image" content="([^"]+)"/', $html, $imageMatches);
    if (isset($imageMatches[1])) {
        echo json_encode([
            'success' => true,
            'type' => 'image',
            'url' => $imageMatches[1],
            'download_url' => 'download.php?url=' . urlencode($imageMatches[1])
        ]);
        exit;
    }
    
    echo json_encode(['error' => 'Could not find media in this post']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
