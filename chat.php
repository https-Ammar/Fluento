<?php
session_start();
require 'db.php';
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>تطبيق الشات</title>
</head>

<body>

    <!-- ✅ زر فتح البروفايل -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="showProfile(<?= $_SESSION['user_id'] ?>)">👤 البروفايل</button>
    <?php endif; ?>

    <!-- ✅ نافذة البروفايل -->
    <div id="profileModal" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%);
background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:999;">
        <button onclick="closeProfile()" style="float:right;">❌</button>
        <h2>تعديل البروفايل</h2>
        <form id="profileForm" enctype="multipart/form-data">
            <label>الاسم:</label><br>
            <input type="text" name="full_name" id="profileName"><br><br>

            <label>البريد الإلكتروني:</label><br>
            <input type="email" name="email" id="profileEmail"><br><br>

            <label>الصورة الحالية:</label><br>
            <img id="profileImage" src="" style="width:80px; height:80px; border-radius:50%;"><br><br>

            <label>تغيير الصورة:</label><br>
            <input type="file" name="profile_image"><br><br>

            <p><strong>الحالة:</strong> <span id="profileStatus"></span></p><br>

            <button type="submit">💾 حفظ التغييرات</button>
        </form>
    </div>

    <script>
        // ✅ إظهار بيانات البروفايل
        function showProfile(userId) {
            fetch('profile_info.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('profileName').value = data.user.full_name;
                        document.getElementById('profileEmail').value = data.user.email;
                        document.getElementById('profileImage').src = data.user.profile_image;
                        document.getElementById('profileStatus').innerText = data.user.is_online == 1 ? '🟢 متصل' : '🔴 غير متصل';
                        document.getElementById('profileModal').style.display = 'block';
                    } else {
                        alert(data.message);
                    }
                });
        }

        // ✅ إغلاق نافذة البروفايل
        function closeProfile() {
            document.getElementById('profileModal').style.display = 'none';
        }

        // ✅ تحديث بيانات البروفايل
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = new FormData(this);

            fetch('update_profile.php', {
                method: 'POST',
                body: form
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('✅ تم التحديث بنجاح');
                        closeProfile();
                    } else {
                        alert(data.message);
                    }
                });
        });
    </script>
</body>

</html>