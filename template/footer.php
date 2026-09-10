</main>
<!-- END MAIN CONTENT -->


</div>
<!-- END APP WRAPPER -->


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- =========================================================
     SIDEBAR SCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const body = document.body;

    const sidebar = document.getElementById("sidebar");

    const sidebarToggle = document.getElementById("sidebarToggle");

    const mobileMenuButton = document.getElementById("mobileMenuButton");

    const sidebarOverlay = document.getElementById("sidebarOverlay");


    /* =====================================================
       CEK ELEMENT
    ====================================================== */

    if (!sidebar) {
        console.warn("Sidebar tidak ditemukan.");
        return;
    }


    /* =====================================================
       DESKTOP SIDEBAR TOGGLE
    ====================================================== */

    if (sidebarToggle) {

        sidebarToggle.addEventListener("click", function (e) {

            e.preventDefault();

            e.stopPropagation();


            /*
             * Jangan gunakan collapsed
             * untuk mode mobile.
             */

            if (window.innerWidth > 991) {

                body.classList.toggle("sidebar-collapsed");


                /*
                 * Simpan status sidebar
                 * supaya tidak berubah ketika
                 * pindah halaman.
                 */

                if (body.classList.contains("sidebar-collapsed")) {

                    localStorage.setItem(
                        "inventory_sidebar",
                        "collapsed"
                    );

                } else {

                    localStorage.setItem(
                        "inventory_sidebar",
                        "expanded"
                    );

                }

            }

        });

    }


    /* =====================================================
       RESTORE DESKTOP SIDEBAR
    ====================================================== */

    function restoreSidebar() {

        if (window.innerWidth > 991) {

            const savedState =
                localStorage.getItem("inventory_sidebar");

            if (savedState === "collapsed") {

                body.classList.add("sidebar-collapsed");

            } else {

                body.classList.remove("sidebar-collapsed");

            }

        } else {

            body.classList.remove("sidebar-collapsed");

        }

    }


    restoreSidebar();


    /* =====================================================
       MOBILE MENU OPEN
    ====================================================== */

    if (mobileMenuButton) {

        mobileMenuButton.addEventListener("click", function () {

            if (window.innerWidth <= 991) {

                body.classList.toggle("sidebar-mobile-open");


                /*
                 * Ganti icon
                 */

                const icon =
                    mobileMenuButton.querySelector("i");

                if (icon) {

                    if (
                        body.classList.contains(
                            "sidebar-mobile-open"
                        )
                    ) {

                        icon.className = "bi bi-x-lg";

                    } else {

                        icon.className = "bi bi-list";

                    }

                }

            }

        });

    }


    /* =====================================================
       CLOSE MOBILE SIDEBAR
    ====================================================== */

    if (sidebarOverlay) {

        sidebarOverlay.addEventListener("click", function () {

            body.classList.remove(
                "sidebar-mobile-open"
            );


            const icon =
                mobileMenuButton?.querySelector("i");

            if (icon) {

                icon.className = "bi bi-list";

            }

        });

    }


    /* =====================================================
       KLIK MENU DI MOBILE
    ====================================================== */

    const sidebarLinks =
        sidebar.querySelectorAll("a");


    sidebarLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            if (window.innerWidth <= 991) {

                body.classList.remove(
                    "sidebar-mobile-open"
                );


                const icon =
                    mobileMenuButton?.querySelector("i");

                if (icon) {

                    icon.className = "bi bi-list";

                }

            }

        });

    });


    /* =====================================================
       RESIZE WINDOW
    ====================================================== */

    let previousWidth = window.innerWidth;


    window.addEventListener("resize", function () {

        const currentWidth = window.innerWidth;


        /*
         * Dari desktop ke mobile
         */

        if (
            previousWidth > 991 &&
            currentWidth <= 991
        ) {

            body.classList.remove(
                "sidebar-mobile-open"
            );

            body.classList.remove(
                "sidebar-collapsed"
            );

        }


        /*
         * Dari mobile ke desktop
         */

        if (
            previousWidth <= 991 &&
            currentWidth > 991
        ) {

            body.classList.remove(
                "sidebar-mobile-open"
            );

            restoreSidebar();

        }


        previousWidth = currentWidth;

    });


});

</script>

</body>
</html>