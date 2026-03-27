<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$role = $_SESSION['role'];
if ($role !== 'Administrator Utama') {
    $_SESSION['error'] = "Aksi gagal. Hanya Administrator Utama yang diizinkan untuk mengacak ulang piket.";
    header("Location: ../views/piket_proyektor");
    exit;
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

if (!$periode_id) {
    $_SESSION['error'] = "Tidak ada periode aktif.";
    header("Location: ../views/piket_proyektor");
    exit;
}

// 1. Ambil Semua Mahasiswa & Pisah per Gender
$boys_master = [];
$girls_master = [];
$res_mhs = mysqli_query($koneksi, "SELECT id, jenis_kelamin FROM mahasiswa");
while ($row = mysqli_fetch_assoc($res_mhs)) {
    if ($row['jenis_kelamin'] === 'Laki-laki') {
        $boys_master[] = $row['id'];
    } else {
        $girls_master[] = $row['id'];
    }
}
$total_mhs = count($boys_master) + count($girls_master);

// 2. Ambil Daftar Jadwal Kuliah (Offline)
$one_week_schedules = [];
$res_jadwal = mysqli_query($koneksi, "
    SELECT jm.id, jm.matkul_id
    FROM jadwal_matkul jm 
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id 
    WHERE jm.periode_id = $periode_id 
    AND (r.nama_ruangan IS NULL OR r.nama_ruangan != 'ONLINE')
    ORDER BY FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jm.id
");
while ($row = mysqli_fetch_assoc($res_jadwal)) {
    $one_week_schedules[] = $row;
}

if (empty($one_week_schedules)) {
    $_SESSION['error'] = "Tidak ada jadwal mata kuliah (offline) di periode ini.";
    header("Location: ../views/piket_proyektor");
    exit;
}

// 3. Ambil Data Enrolment (KRS)
$course_enrollments = [];
$q_krs = "SELECT matkul_id, mahasiswa_id FROM krs WHERE periode_id = $periode_id";
$res_krs = mysqli_query($koneksi, $q_krs);
while ($row = mysqli_fetch_assoc($res_krs)) {
    $course_enrollments[$row['matkul_id']][] = $row['mahasiswa_id'];
}

// 4. Hapus Data Piket Lama (Semua atau Blok Tertentu)
$filter_blok_target = isset($_GET['blok']) ? intval($_GET['blok']) : null;

if ($filter_blok_target) {
    $w_start = ($filter_blok_target * 2) - 1;
    $w_end = $filter_blok_target * 2;
    mysqli_query($koneksi, "DELETE FROM piket_proyektor WHERE periode_id = $periode_id AND (minggu_ke = $w_start OR minggu_ke = $w_end)");
} else {
    mysqli_query($koneksi, "DELETE FROM piket_proyektor WHERE periode_id = $periode_id");
}

// 5. Algoritma "Bagi Habis" Eksak dengan Pengelompokan Jenis Kelamin (Siklus 2 Mingguan)
if ($filter_blok_target) {
    // MODE: ACAK 1 BLOK (2 MINGGU) TERTENTU
    $block = $filter_blok_target - 1;
    $boys_pool = $boys_master;
    $girls_pool = $girls_master;
    shuffle($boys_pool);
    shuffle($girls_pool);

    // Siapkan 14 slot untuk 2 minggu ini
    $block_slots = [];
    for ($w = 1; $w <= 2; $w++) {
        foreach ($one_week_schedules as $sch) {
            $block_slots[] = [
                'week' => ($block * 2) + $w,
                'jid' => $sch['id'],
                'mid' => $sch['matkul_id']
            ];
        }
    }

    $total_slots = count($block_slots);
    if ($total_slots > 0) {
        $base_per_slot = floor($total_mhs / $total_slots);
        $remainder = $total_mhs % $total_slots;
        $counts_per_slot = [];
        for ($i = 0; $i < $total_slots; $i++) {
            $counts_per_slot[$i] = $base_per_slot + ($i < $remainder ? 1 : 0);
        }
        shuffle($counts_per_slot);

        foreach ($block_slots as $index => $slot) {
            $needed = $counts_per_slot[$index];
            $eligible_all = $course_enrollments[$slot['mid']] ?? [];
            $eligible_boys = array_intersect($boys_pool, $eligible_all);
            $eligible_girls = array_intersect($girls_pool, $eligible_all);

            $selected = [];
            $boy_count = count($boys_pool);
            $girl_count = count($girls_pool);
            $priority_boys = ($boy_count >= $girl_count);

            if ($needed % 2 != 0) {
                if ($boy_count % 2 != 0 && $girl_count % 2 == 0) $priority_boys = true;
                else if ($girl_count % 2 != 0 && $boy_count % 2 == 0) $priority_boys = false;
            }

            if ($priority_boys) {
                if (count($eligible_boys) >= $needed) {
                    shuffle($eligible_boys);
                    $selected = array_slice($eligible_boys, 0, $needed);
                    $boys_pool = array_diff($boys_pool, $selected);
                } elseif (count($eligible_girls) >= $needed) {
                    shuffle($eligible_girls);
                    $selected = array_slice($eligible_girls, 0, $needed);
                    $girls_pool = array_diff($girls_pool, $selected);
                }
            } else {
                if (count($eligible_girls) >= $needed) {
                    shuffle($eligible_girls);
                    $selected = array_slice($eligible_girls, 0, $needed);
                    $girls_pool = array_diff($girls_pool, $selected);
                } elseif (count($eligible_boys) >= $needed) {
                    shuffle($eligible_boys);
                    $selected = array_slice($eligible_boys, 0, $needed);
                    $boys_pool = array_diff($boys_pool, $selected);
                }
            }

            if (count($selected) < $needed) {
                $avail_pool = array_intersect(array_merge($boys_pool, $girls_pool), $eligible_all);
                if (!empty($avail_pool)) {
                    shuffle($avail_pool);
                    $take = min(count($avail_pool), $needed - count($selected));
                    $selected = array_merge($selected, array_slice($avail_pool, 0, $take));
                    $boys_pool = array_diff($boys_pool, $selected);
                    $girls_pool = array_diff($girls_pool, $selected);
                }
            }

            if (count($selected) < $needed) {
                $extra_eligible = array_diff($eligible_all, $selected);
                if (!empty($extra_eligible)) {
                    shuffle($extra_eligible);
                    $take = min(count($extra_eligible), $needed - count($selected));
                    $selected = array_merge($selected, array_slice($extra_eligible, 0, $take));
                }
            }

            foreach ($selected as $student_id) {
                $student_id = intval($student_id);
                mysqli_query($koneksi, "INSERT INTO piket_proyektor (jadwal_matkul_id, mahasiswa_id, minggu_ke, periode_id) VALUES ({$slot['jid']}, $student_id, {$slot['week']}, $periode_id)");
            }
        }
    }
} else {
    // MODE: ACAK SEMUA 18 MINGGU
    for ($block = 0; $block < 9; $block++) {
    $boys_pool = $boys_master;
    $girls_pool = $girls_master;
    shuffle($boys_pool);
    shuffle($girls_pool);

    // Siapkan 14 slot untuk 2 minggu ini
    $block_slots = [];
    for ($w = 1; $w <= 2; $w++) {
        foreach ($one_week_schedules as $sch) {
            $block_slots[] = [
                'week' => ($block * 2) + $w,
                'jid' => $sch['id'],
                'mid' => $sch['matkul_id']
            ];
        }
    }

    $total_slots = count($block_slots);
    if ($total_slots == 0) continue;

    // Tentukan jumlah orang per slot (misal 35 mhs / 14 slot)
    $base_per_slot = floor($total_mhs / $total_slots);
    $remainder = $total_mhs % $total_slots;
    $counts_per_slot = [];
    for ($i = 0; $i < $total_slots; $i++) {
        $counts_per_slot[$i] = $base_per_slot + ($i < $remainder ? 1 : 0);
    }
    shuffle($counts_per_slot);

    // Isi setiap slot dengan gender yang seragam sebisa mungkin
    foreach ($block_slots as $index => $slot) {
        $needed = $counts_per_slot[$index];
        $eligible_all = $course_enrollments[$slot['mid']] ?? [];

        $eligible_boys = array_intersect($boys_pool, $eligible_all);
        $eligible_girls = array_intersect($girls_pool, $eligible_all);

        $selected = [];

        // Pilih gender yang pool-nya masih cukup banyak untuk membentuk tim homogen
        // MODIFIKASI: Prioritaskan parity (ganjil/genap) untuk mengakomodasi kelas dengan jumlah ganjil
        // agar sisa (remainder) selalu sesuai dengan gender yang ganjil (laki-laki).
        $boy_count = count($boys_pool);
        $girl_count = count($girls_pool);
        $priority_boys = ($boy_count >= $girl_count);

        if ($needed % 2 != 0) {
            // Jika butuh ganjil (misal 1 atau 3 orang), prioritaskan gender yang pool-nya ganjil
            if ($boy_count % 2 != 0 && $girl_count % 2 == 0) $priority_boys = true;
            else if ($girl_count % 2 != 0 && $boy_count % 2 == 0) $priority_boys = false;
        }

        if ($priority_boys) {
            // Prioritas laki-laki
            if (count($eligible_boys) >= $needed) {
                shuffle($eligible_boys);
                $selected = array_slice($eligible_boys, 0, $needed);
                $boys_pool = array_diff($boys_pool, $selected);
            } elseif (count($eligible_girls) >= $needed) {
                // Jika laki-laki tidak cukup, coba perempuan
                shuffle($eligible_girls);
                $selected = array_slice($eligible_girls, 0, $needed);
                $girls_pool = array_diff($girls_pool, $selected);
            }
        } else {
            // Prioritas perempuan
            if (count($eligible_girls) >= $needed) {
                shuffle($eligible_girls);
                $selected = array_slice($eligible_girls, 0, $needed);
                $girls_pool = array_diff($girls_pool, $selected);
            } elseif (count($eligible_boys) >= $needed) {
                // Jika perempuan tidak cukup, coba laki-laki
                shuffle($eligible_boys);
                $selected = array_slice($eligible_boys, 0, $needed);
                $boys_pool = array_diff($boys_pool, $selected);
            }
        }

        // Failsafe 1: Jika tidak bisa homogen dari pool, coba campur dari pool
        if (count($selected) < $needed) {
            $avail_pool = array_intersect(array_merge($boys_pool, $girls_pool), $eligible_all);
            if (!empty($avail_pool)) {
                shuffle($avail_pool);
                $take = min(count($avail_pool), $needed - count($selected));
                $extra = array_slice($avail_pool, 0, $take);
                $selected = array_merge($selected, $extra);
                $boys_pool = array_diff($boys_pool, $extra);
                $girls_pool = array_diff($girls_pool, $extra);
            }
        }

        // Failsafe 2: Jika pool habis tapi slot butuh orang (untuk matkul bnyk krs)
        if (count($selected) < $needed) {
            $extra_eligible = array_diff($eligible_all, $selected);
            if (!empty($extra_eligible)) {
                shuffle($extra_eligible);
                $take = min(count($extra_eligible), $needed - count($selected));
                $selected = array_merge($selected, array_slice($extra_eligible, 0, $take));
            }
        }

        foreach ($selected as $student_id) {
            $student_id = intval($student_id);
            mysqli_query($koneksi, "INSERT INTO piket_proyektor (jadwal_matkul_id, mahasiswa_id, minggu_ke, periode_id) VALUES ({$slot['jid']}, $student_id, {$slot['week']}, $periode_id)");
        }
    }
}
}

$_SESSION['success'] = "Daftar piket berhasil diacak.";
header("Location: ../views/piket_proyektor");
exit;
