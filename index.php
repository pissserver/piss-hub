<?php
    include "connection.php";
    $version_timbangan = mysql_query("SELECT version FROM update_timbangan ORDER BY id DESC LIMIT 1");
    $v_timbangan = mysql_fetch_array($version_timbangan);
    $version_lapak = mysql_query("SELECT version FROM update_lapak ORDER BY id DESC LIMIT 1");
    $v_lapak = mysql_fetch_array($version_lapak);
    $version_listrik = mysql_query("SELECT version FROM update_listrik ORDER BY id DESC LIMIT 1");
    $v_listrik = mysql_fetch_array($version_listrik);
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <title>Login | PISS Hub</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="icon" type="image/png" href="timbangan/img/icon.png" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/bootstrap/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/fonts/font-awesome-4.7.0/css/font-awesome.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/fonts/Linearicons-Free-v1.0.0/icon-font.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/animate/animate.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/css-hamburgers/hamburgers.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/animsition/css/animsition.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/select2/select2.min.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/vendor/daterangepicker/daterangepicker.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/css/util.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/css/login.css" />
        <link rel="stylesheet" type="text/css" href="timbangan/css/hub-login.css" />
    </head>
    <body class="hub-login-page">
        <div class="limiter">
            <div class="container-login100">
                <div class="wrap-login100">
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <form method="post" action="hub_checklogin.php" class="login100-form hub-login-form validate-form">
                        <div class="hub-login-brand">
                            <img src="timbangan/img/logo.png" alt="" />
                            <span class="hub-login-brand-copy">
                                <span class="hub-login-brand-name">PISS HUB</span>
                                <span class="hub-login-brand-caption">OPERATIONAL WORKSPACE</span>
                            </span>
                        </div>

                        <span class="login100-form-title">
                            Selamat datang kembali
                        </span>
                        <p class="hub-login-intro">
                            Masuk untuk melanjutkan ke dashboard operasional Anda.
                        </p>

                        <div class="hub-version-list" aria-label="Versi aplikasi">
                            <span class="hub-version"><i class="fa fa-balance-scale" aria-hidden="true"></i> Timbangan v<?php echo $v_timbangan['version']; ?></span>
                            <span class="hub-version"><i class="fa fa-building" aria-hidden="true"></i> Lapak v<?php echo $v_lapak['version']; ?></span>
                            <span class="hub-version"><i class="fa fa-bolt" aria-hidden="true"></i> Listrik v<?php echo $v_listrik['version']; ?></span>
                        </div>

                        <div class="wrap-input100 validate-input" data-validate="Username is required">
                            <i class="fa fa-user hub-input-icon" aria-hidden="true"></i>
                            <input id="user" class="form-control input100" type="text" name="user" autocomplete="username" aria-label="Username" required />
                            <span class="focus-input100"></span>
                            <span class="label-input100">Masukkan Username</span>
                        </div>

                        <div class="wrap-input100 validate-input" data-validate="Password is required">
                            <i class="fa fa-lock hub-input-icon" aria-hidden="true"></i>
                            <input id="password" class="form-control input100" type="password" name="pass" autocomplete="current-password" aria-label="Password" required />
                            <span class="focus-input100"></span>
                            <span class="label-input100">Masukkan Password</span>
                            <button type="button" class="hub-password-toggle" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="flex-sb-m w-full p-t-3 p-b-32">
                            <div class="contact100-form-checkbox">
                                <input class="input-checkbox100" id="ckb1" type="checkbox" name="remember" />
                                <label class="label-checkbox100" for="ckb1">Ingat saya</label>
                            </div>
                        </div>

                        <div class="container-login100-form-btn">
                            <button class="login100-form-btn">
                                Masuk ke dashboard
                                <i class="fa fa-arrow-right" aria-hidden="true" style="margin-left: 10px"></i>
                            </button>
                        </div>
                        <p class="hub-login-note"><i class="fa fa-lock" aria-hidden="true"></i> Akses aman untuk operasional PISS</p>
                    </form>

                    <div class="login100-more">
                        <div class="text-overlay hub-hero-copy">
                            <span class="hub-hero-eyebrow">Satu ruang kerja. Tiga layanan.</span>
                            <h1>Operasional bisnis dalam kendali.</h1>
                            <div class="hub-hero-subtitle" id="subtitle-text"></div>
                            <div class="hub-service-list" aria-label="Layanan PISS Hub">
                                <span class="hub-service"><i class="fa fa-balance-scale" aria-hidden="true"></i> Timbangan</span>
                                <span class="hub-service"><i class="fa fa-building" aria-hidden="true"></i> Lapak</span>
                                <span class="hub-service"><i class="fa fa-bolt" aria-hidden="true"></i> Listrik</span>
                            </div>
                        </div>
                        <video loop muted playsinline preload="metadata" class="background-video" aria-hidden="true">
                            <source src="timbangan/img/login.mp4" type="video/mp4" />
                        </video>
                        <div class="overlay"></div>
                        <div class="toast" role="group" aria-label="Pesan motivasi">
                            <div class="toast-header">
                                <img src="timbangan/img/logo.png" alt="" />
                                <strong class="me-auto">Developer PISS</strong>
                                <small>Sekarang</small>
                                <button
                                    type="button"
                                    class="btn-close"
                                    onclick="this.parentElement.parentElement.remove();"
                                    aria-label="Tutup notifikasi"
                                >&times;</button>
                            </div>
                            <div class="toast-body">
                                <i class="fa fa-quote-left" aria-hidden="true"></i>
                                <span id="motivationMessage">Mulai hari ini dengan semangat, selesaikan dengan bangga.</span>
                            </div>
                        </div>
                        <button type="button" class="hub-media-toggle" id="toggleHeroVideo" aria-label="Jeda video latar" aria-pressed="false">
                            <i class="fa fa-pause" aria-hidden="true"></i>
                            <span>Jeda animasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script src="timbangan/vendor/jquery/jquery-3.2.1.min.js"></script>
        <script src="timbangan/vendor/animsition/js/animsition.min.js"></script>
        <script src="timbangan/vendor/bootstrap/js/popper.js"></script>
        <script src="timbangan/vendor/bootstrap/js/bootstrap.min.js"></script>
        <script src="timbangan/vendor/select2/select2.min.js"></script>
        <script src="timbangan/vendor/daterangepicker/moment.min.js"></script>
        <script src="timbangan/vendor/daterangepicker/daterangepicker.js"></script>
        <script src="timbangan/vendor/countdowntime/countdowntime.js"></script>
        <script src="timbangan/js/login.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("user").focus();
            });

            document.getElementById("togglePassword").addEventListener("click", function () {
                const passwordField = document.getElementById("password");
                const icon = this.querySelector("i");
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    icon.classList.add("fa-eye-slash");
                    icon.classList.remove("fa-eye");
                    this.setAttribute("aria-label", "Sembunyikan password");
                    this.setAttribute("aria-pressed", "true");
                } else {
                    passwordField.type = "password";
                    icon.classList.add("fa-eye");
                    icon.classList.remove("fa-eye-slash");
                    this.setAttribute("aria-label", "Tampilkan password");
                    this.setAttribute("aria-pressed", "false");
                }
            });

            const heroVideo = document.querySelector(".background-video");
            const heroVideoToggle = document.getElementById("toggleHeroVideo");
            const heroVideoToggleIcon = heroVideoToggle.querySelector("i");
            const heroVideoToggleText = heroVideoToggle.querySelector("span");
            const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
            const motivationMessage = document.getElementById("motivationMessage");
            const motivationToast = motivationMessage.closest(".toast");
            const motivationMessages = [
                "Mulai hari ini dengan semangat, selesaikan dengan bangga.",
                "Langkah kecil yang konsisten membawa hasil besar.",
                "Kerja cerdas, tetap fokus, dan terus bertumbuh.",
                "Setiap tantangan adalah kesempatan untuk jadi lebih baik.",
                "Bersama, kita bisa mencapai lebih banyak."
            ];
            let motivationIndex = 0;

            window.setInterval(function () {
                if (!motivationToast.isConnected) return;
                motivationMessage.classList.add("is-changing");
                window.setTimeout(function () {
                    if (!motivationToast.isConnected) return;
                    motivationIndex = (motivationIndex + 1) % motivationMessages.length;
                    motivationMessage.textContent = motivationMessages[motivationIndex];
                    motivationMessage.classList.remove("is-changing");
                }, 250);
            }, 6000);

            function updateHeroVideoToggle(isPaused) {
                heroVideoToggle.setAttribute("aria-pressed", String(isPaused));
                heroVideoToggle.setAttribute("aria-label", isPaused ? "Putar video latar" : "Jeda video latar");
                heroVideoToggleIcon.classList.toggle("fa-play", isPaused);
                heroVideoToggleIcon.classList.toggle("fa-pause", !isPaused);
                heroVideoToggleText.textContent = isPaused ? "Putar animasi" : "Jeda animasi";
            }

            if (prefersReducedMotion.matches) {
                heroVideo.pause();
                updateHeroVideoToggle(true);
            } else {
                heroVideo.play().then(function () {
                    updateHeroVideoToggle(false);
                }).catch(function () {
                    updateHeroVideoToggle(true);
                });
            }

            heroVideoToggle.addEventListener("click", function () {
                if (heroVideo.paused) {
                    heroVideo.play().then(function () {
                        updateHeroVideoToggle(false);
                    }).catch(function () {
                        updateHeroVideoToggle(true);
                    });
                } else {
                    heroVideo.pause();
                    updateHeroVideoToggle(true);
                }
            });

            const handleReducedMotionChange = function (event) {
                if (event.matches) {
                    heroVideo.pause();
                    updateHeroVideoToggle(true);
                }
            };
            if (prefersReducedMotion.addEventListener) {
                prefersReducedMotion.addEventListener("change", handleReducedMotionChange);
            } else if (prefersReducedMotion.addListener) {
                prefersReducedMotion.addListener(handleReducedMotionChange);
            }

            function setCookie(name, value, days) {
                let expires = "";
                if (days) {
                    const date = new Date();
                    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            }

            function getCookie(name) {
                const nameEQ = name + "=";
                const ca = document.cookie.split(";");
                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i];
                    while (c.charAt(0) == " ") c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }

            function eraseCookie(name) {
                document.cookie = name + "=; Max-Age=-99999999;";
            }

            document.addEventListener("DOMContentLoaded", function () {
                const usernameInput = document.getElementById("user");
                const passwordInput = document.getElementById("password");
                const rememberCheckbox = document.getElementById("ckb1");

                if (getCookie("user") && getCookie("password")) {
                    usernameInput.value = getCookie("user");
                    passwordInput.value = getCookie("password");
                    rememberCheckbox.checked = true;
                }

                const form = document.querySelector("form");
                form.addEventListener("submit", function () {
                    if (rememberCheckbox.checked) {
                        setCookie("user", usernameInput.value, 7);
                        setCookie("password", passwordInput.value, 7);
                    } else {
                        eraseCookie("user");
                        eraseCookie("password");
                    }
                });
            });
        </script>
    </body>
</html>
