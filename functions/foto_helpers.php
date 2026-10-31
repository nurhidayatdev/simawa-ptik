<?php
// functions/foto_helpers.php
// Helper functions for automatic initials-based profile avatars (No profile photo files needed)

/**
 * Extract 2-letter uppercase initials from full name
 * E.g. "Nur Hidayat" -> "NH", "Raynato" -> "RA", "Administrator SIMAWA" -> "AS"
 * 
 * @param string|null $nama Full name
 * @return string 2-letter uppercase initials
 */
function getInitials($nama)
{
    $nama = trim($nama ?? '');
    if (empty($nama)) {
        return 'U';
    }

    // Clean punctuation and titles (e.g. S.Kom, M.Pd, etc.)
    $cleaned = preg_replace('/[,.]/', '', $nama);
    $words = preg_split('/\s+/', $cleaned);

    if (count($words) >= 2) {
        $first = mb_substr($words[0], 0, 1, 'UTF-8');
        $second = mb_substr($words[1], 0, 1, 'UTF-8');
        return strtoupper($first . $second);
    } else {
        return strtoupper(mb_substr($cleaned, 0, 2, 'UTF-8'));
    }
}

/**
 * Generate an inline SVG Data URI for an initials avatar with deterministic colorful gradient background
 * 
 * @param string|null $nama Full name
 * @param int $size Width and height in px
 * @return string data:image/svg+xml;utf8,... URL
 */
function getInitialsAvatarSvg($nama, $size = 120)
{
    $initials = getInitials($nama);

    // Harmonious modern gradient color palette
    $palette = [
        ['#4f46e5', '#3730a3'], // Indigo
        ['#2563eb', '#1d4ed8'], // Blue
        ['#0284c7', '#0369a1'], // Sky
        ['#0d9488', '#0f766e'], // Teal
        ['#059669', '#047857'], // Emerald
        ['#d97706', '#b45309'], // Amber
        ['#e11d48', '#be123c'], // Rose
        ['#7c3aed', '#6d28d9'], // Purple
        ['#c026d3', '#a21caf'], // Fuchsia
        ['#6366f1', '#4338ca'], // Violet
        ['#0891b2', '#0e7490'], // Cyan
        ['#ea580c', '#c2410c']  // Orange
    ];

    $hash = abs(crc32($nama ?? ''));
    $colorPair = $palette[$hash % count($palette)];
    $c1 = $colorPair[0];
    $c2 = $colorPair[1];
    $fontSize = round($size * 0.42);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '">'
         . '<defs>'
         . '<linearGradient id="grad' . $hash . '" x1="0%" y1="0%" x2="100%" y2="100%">'
         . '<stop offset="0%" stop-color="' . $c1 . '"/>'
         . '<stop offset="100%" stop-color="' . $c2 . '"/>'
         . '</linearGradient>'
         . '</defs>'
         . '<rect width="' . $size . '" height="' . $size . '" rx="' . ($size / 2) . '" fill="url(#grad' . $hash . ')"/>'
         . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Poppins, sans-serif" font-size="' . $fontSize . '" font-weight="700" letter-spacing="0.5">' . htmlspecialchars($initials) . '</text>'
         . '</svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/**
 * Universal profile avatar URL generator.
 * Backwards compatible with legacy calls expecting a URL string.
 *
 * @param string $nameOrFoto Full name, legacy photo filename, or identifier
 * @param string $base Base path (kept for backwards compatibility)
 * @param string $fallback Alternative name to use if first arg is empty
 * @return string Full SVG data URL
 */
function profilePhotoUrl($nameOrFoto = '', $base = '', $fallback = '')
{
    $name = trim($nameOrFoto ?? '');

    // If identifier is empty or looks like a file extension (.jpg, .png, etc.), use fallback
    if (empty($name) || preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $name)) {
        if (!empty($fallback)) {
            $name = $fallback;
        } elseif (!empty($_SESSION['nama_lengkap'])) {
            $name = $_SESSION['nama_lengkap'];
        } elseif (!empty($name)) {
            $name = pathinfo($name, PATHINFO_FILENAME);
        } else {
            $name = 'Pengguna';
        }
    }

    return getInitialsAvatarSvg($name);
}

/**
 * Backward compatibility wrapper
 */
function getProfilePhoto($nameOrFoto, $base = '../')
{
    return profilePhotoUrl($nameOrFoto, $base);
}
