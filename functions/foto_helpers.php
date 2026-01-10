<?php
// functions/foto_helpers.php
// Helper function to get profile photo URL with cache busting

function getProfilePhoto($foto, $base = '../')
{
    if (empty($foto)) {
        return $base . 'assets/image/logo.webp';
    }

    $path = $base . 'storage/profile/' . $foto;
    $absolute = realpath(__DIR__ . '/../storage/profile/' . $foto);

    // Append file modification time as cache buster
    if ($absolute && file_exists($absolute)) {
        $mtime = filemtime($absolute);
        return $path . '?v=' . $mtime;
    }

    // Fallback: use current timestamp if file doesn't exist
    return $path . '?v=' . time();
}

/**
 * Get profile photo path with cache busting for relative paths
 * @param string $foto - filename of the photo
 * @param string $base - base path prefix (e.g., '../' or '')
 * @return string full path with cache busting query parameter
 */
function profilePhotoUrl($foto, $base = '')
{
    if (empty($foto)) {
        return $base . 'assets/image/logo.webp';
    }

    // Try to get the absolute path to check file modification time
    $storage_base = __DIR__ . '/../storage/profile/';
    $absolute = realpath($storage_base . $foto);

    $path = $base . 'storage/profile/' . htmlspecialchars($foto);

    if ($absolute && file_exists($absolute)) {
        return $path . '?v=' . filemtime($absolute);
    }

    // Fallback with timestamp if file not found
    return $path . '?v=' . time();
}
