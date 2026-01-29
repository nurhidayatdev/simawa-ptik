<?php
session_start();
include '../koneksi.php';

$nama_panggilan = $_SESSION['nama_panggilan'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';
$query = "SELECT id FROM mahasiswa WHERE user_id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$mahasiswa_id = $mahasiswa['id'];


// ambil data user
$query = "
SELECT 
    u.id AS user_id,
    u.role,
    u.password,
    m.nim,
    m.nama_lengkap,
    m.nama_panggilan,
    u.email,
    m.foto,
    m.instagram,
    m.bio
FROM users u
JOIN mahasiswa m ON m.user_id = u.id
WHERE u.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);



if (isset($_POST['update'])) {

    $foto_baru = $data['foto']; // default foto lama

    // prefer client-side processed image in foto_data (base64)
    if (!empty($_POST['foto_data'])) {
        $foto_data = $_POST['foto_data'];
        // data URIs look like: data:image/jpeg;base64,....
        if (preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $foto_data, $m)) {
            $mime = $m[1];
            $base64 = substr($foto_data, strpos($foto_data, ',') + 1);
            $decoded = base64_decode($base64);
            if ($decoded === false) {
                die('Gagal decode gambar.');
            }

            // If GD functions available, use them to resize/convert; otherwise write raw bytes
            if (function_exists('imagecreatefromstring')) {
                $src = @imagecreatefromstring($decoded);
                if ($src === false) {
                    die('Format gambar tidak dikenali.');
                }

                // ensure square output (client already crops to square), resize to 512x512
                $size = 512;
                $dst = imagecreatetruecolor($size, $size);
                // preserve transparency for PNG/WebP
                imagesavealpha($dst, true);
                $trans_col = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefill($dst, 0, 0, $trans_col);
                $w = imagesx($src);
                $h = imagesy($src);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $w, $h);

                // prefer webp if available
                if (function_exists('imagewebp')) {
                    $ext = 'webp';
                    $foto_baru = $nama_panggilan . '.' . $ext;
                    imagewebp($dst, __DIR__ . "/../storage/profile/" . $foto_baru, 80);
                } else {
                    $ext = 'jpg';
                    $foto_baru = $nama_panggilan . '.' . $ext;
                    imagejpeg($dst, __DIR__ . "/../storage/profile/" . $foto_baru, 85);
                }

                $__img_destroy = 'imagedestroy';
                if (function_exists($__img_destroy)) {
                    $__img_destroy($src);
                }
                if (function_exists($__img_destroy)) {
                    $__img_destroy($dst);
                }
            } else {
                // GD not available: determine extension from mime and save raw bytes
                $ext = 'jpg';
                if ($mime === 'image/webp') $ext = 'webp';
                elseif ($mime === 'image/png') $ext = 'png';
                elseif ($mime === 'image/jpeg' || $mime === 'image/jpg') $ext = 'jpg';
                $foto_baru = $nama_panggilan . '.' . $ext;
                $dest = __DIR__ . "/../storage/profile/" . $foto_baru;
                file_put_contents($dest, $decoded);
            }
        }
    } elseif (!empty($_FILES['foto_file']['name'])) {

        $nama_file = $_FILES['foto_file']['name'];
        $tmp_file  = $_FILES['foto_file']['tmp_name'];
        $ext       = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            // fallback: move uploaded then try to compress using GD
            $foto_baru = $nama_panggilan . '.' . $ext;
            $dest_path = __DIR__ . "/../storage/profile/" . $foto_baru;
            move_uploaded_file($tmp_file, $dest_path);
            // attempt to compress/resize
            // attempt to compress/resize if GD available
            if (function_exists('imagecreatefromstring')) {
                $src = @imagecreatefromstring(file_get_contents($dest_path));
                if ($src !== false) {
                    $size = 512;
                    $dst = imagecreatetruecolor($size, $size);
                    imagesavealpha($dst, true);
                    $trans_col = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefill($dst, 0, 0, $trans_col);
                    $w = imagesx($src);
                    $h = imagesy($src);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $w, $h);
                    if (function_exists('imagewebp')) {
                        imagewebp($dst, $dest_path, 80);
                    } else {
                        imagejpeg($dst, $dest_path, 85);
                    }
                    $__img_destroy = 'imagedestroy';
                    if (function_exists($__img_destroy)) {
                        $__img_destroy($src);
                    }
                    if (function_exists($__img_destroy)) {
                        $__img_destroy($dst);
                    }
                }
            }
        } else {
            die("Format foto tidak valid!");
        }
    }


    mysqli_query($koneksi, "UPDATE mahasiswa SET 
        nama_lengkap='{$_POST['nama_lengkap']}',
        bio='{$_POST['bio']}',
        instagram='{$_POST['instagram']}',
        foto='$foto_baru'
        WHERE user_id='$user_id'
    ");

    // Server-side password validation
    $password = $_POST['password'];
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $password)) {
        $error_message = "Password must be at least 8 characters, contain uppercase, lowercase, numbers, and no symbols.";
        include '../includes/alert_actions.php';
        exit;
    }

    mysqli_query($koneksi, "UPDATE users SET 
        email='{$_POST['email']}',
        password='{$password}'
        WHERE id='$user_id'
    ");

    $redirect = "../views/profile.php";







    include '../includes/alert_actions.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Edit Profile - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 md:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-gray-200 p-6 flex-grow w-full">

            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-gray-200 pb-6">



                    <h3 class="text-2xl font-bold mb-6">Edit Profile</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?= $data['nama_lengkap']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                            <input type="text" name="email" value="<?= $data['email']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                            <input type="text" name="password" value="<?= htmlspecialchars($data['password']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs"
                                minlength="8"
                                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$"
                                title="Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and NO symbols."
                                required>
                            <p class="mt-1 text-[10px] text-gray-500 italic">* Minimal 8 karakter, harus ada huruf besar, kecil, angka, dan tanpa simbol.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Instagram</label>
                            <input name="instagram" value="<?= $data['instagram']; ?>" placeholder="@Username"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" readonly></input>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Bio</label>
                            <input name="bio" value="<?= $data['bio']; ?>" maxlength="35"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs"></input>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Foto</label>
                            <input type="file" id="foto-input" name="foto_file" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            <input type="hidden" name="foto_data" id="foto_data">
                            <input type="hidden" name="current_foto" value="<?= htmlspecialchars($data['foto']); ?>">

                            <div class="flex items-center space-x-4 mt-4">
                                <img id="current-preview" src="<?= profilePhotoUrl($data['foto'], '../') ?>" class="img-fluid rounded" style="width:120px; height:120px; object-fit:cover;" />
                                <div>
                                    <button type="button" id="open-cropper" class="px-3 py-1 bg-indigo-200 rounded text-xs hidden">Buka Cropper</button>
                                </div>
                            </div>

                            <!-- Cropper modal-ish area -->
                            <div id="cropper-area" class="mt-4 hidden">
                                <div class="grid sm:grid-cols-2 gap-4 space-x-4">
                                    <div class="w-full flex align-center justify-center bg-slate-200">
                                        <img id="crop-image" style="max-width:100%; display:block;" />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="border p-2 rounded" style="width:160px; height:160px; overflow:hidden;">
                                            <div class="preview-wrapper" style="width:100%; height:100%;">
                                                <img id="preview" style="width:100%; height:100%; object-fit:cover; display:block;" />
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button type="button" id="zoom-in" class="px-3 py-1 bg-indigo-200 rounded">+</button>
                                            <button type="button" id="zoom-out" class="px-3 py-1 bg-indigo-200 rounded">-</button>
                                            <button type="button" id="rotate-left" class="px-3 py-1 bg-indigo-200 rounded">⟲</button>
                                            <button type="button" id="rotate-right" class="px-3 py-1 bg-indigo-200 rounded">⟳</button>
                                        </div>
                                        <div>
                                            <button type="button" id="apply-crop" class="mt-2 px-3 py-1 bg-indigo-700 text-white rounded text-sm">Gunakan Foto</button>
                                            <button type="button" id="cancel-crop" class="mt-2 ml-2 px-3 py-1 bg-indigo-200 rounded text-sm">Batal</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>





                </div>

                <div class="flex justify-end space-x-4">
                    <a href="../dashboard/profile.php">
                        <button type="button"
                            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-indigo-50 text-xs">Cancel</button>
                    </a>

                    <button type="submit" name="update"
                        class="px-6 py-2 bg-indigo-800 text-white rounded-lg hover:bg-indigo-700 text-xs">Submit</button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        // Image cropper for profile foto
        (function() {
            const fotoInput = document.getElementById('foto-input');
            const cropperArea = document.getElementById('cropper-area');
            const cropImage = document.getElementById('crop-image');
            const currentPreview = document.getElementById('current-preview');
            const fotoDataInput = document.getElementById('foto_data');
            const applyBtn = document.getElementById('apply-crop');
            const cancelBtn = document.getElementById('cancel-crop');
            const zoomIn = document.getElementById('zoom-in');
            const zoomOut = document.getElementById('zoom-out');
            const rotL = document.getElementById('rotate-left');
            const rotR = document.getElementById('rotate-right');
            let cropper = null;
            const form = document.querySelector('form.space-y-6');

            function initCropper() {
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    background: false,
                    autoCropArea: 1,
                    responsive: true,
                    preview: '.preview-wrapper'
                });
            }

            fotoInput.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                cropImage.src = url;
                cropperArea.classList.remove('hidden');
                cropImage.onload = () => {
                    initCropper();
                };
            });

            zoomIn.addEventListener('click', () => {
                if (cropper) cropper.zoom(0.1);
            });
            zoomOut.addEventListener('click', () => {
                if (cropper) cropper.zoom(-0.1);
            });
            rotL.addEventListener('click', () => {
                if (cropper) cropper.rotate(-90);
            });
            rotR.addEventListener('click', () => {
                if (cropper) cropper.rotate(90);
            });

            cancelBtn.addEventListener('click', () => {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropperArea.classList.add('hidden');
                fotoInput.value = '';
            });

            applyBtn.addEventListener('click', () => {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingQuality: 'high'
                });
                canvas.toBlob((blob) => {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        fotoDataInput.value = reader.result; // base64 data URL
                        currentPreview.src = reader.result;
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        cropperArea.classList.add('hidden');
                    };
                    reader.readAsDataURL(blob);
                }, 'image/webp', 0.8);
            });

            form.addEventListener('submit', function(e) {
                if (cropper && !fotoDataInput.value) {
                    e.preventDefault();
                    const canvas = cropper.getCroppedCanvas({
                        width: 512,
                        height: 512,
                        imageSmoothingQuality: 'high'
                    });
                    canvas.toBlob((blob) => {
                        const reader = new FileReader();
                        reader.onloadend = () => {
                            fotoDataInput.value = reader.result;
                            if (cropper) {
                                cropper.destroy();
                                cropper = null;
                            }
                            cropperArea.classList.add('hidden');
                            form.submit();
                        };
                        reader.readAsDataURL(blob);
                    }, 'image/webp', 0.8);
                }
            });
        })();
    </script>
</body>

</html>