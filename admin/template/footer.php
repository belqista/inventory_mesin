</main>
<!-- END ADMIN MAIN CONTENT -->


</div>
<!-- END ADMIN APP WRAPPER -->


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- =========================================================
     ADMIN SIDEBAR SCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const body =
        document.body;

    const sidebar =
        document.getElementById(
            "adminSidebar"
        );

    const sidebarToggle =
        document.getElementById(
            "adminSidebarToggle"
        );

    const mobileButton =
        document.getElementById(
            "adminMobileButton"
        );

    const overlay =
        document.getElementById(
            "adminSidebarOverlay"
        );


    /* =====================================================
       CEK SIDEBAR
    ====================================================== */

    if (!sidebar) {

        console.warn(
            "Admin sidebar tidak ditemukan."
        );

        return;

    }


    /* =====================================================
       DESKTOP TOGGLE
    ====================================================== */

    if (sidebarToggle) {

        sidebarToggle.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();


                if (window.innerWidth > 991) {

                    body.classList.toggle(
                        "admin-sidebar-collapsed"
                    );


                    if (
                        body.classList.contains(
                            "admin-sidebar-collapsed"
                        )
                    ) {

                        localStorage.setItem(
                            "inventory_admin_sidebar",
                            "collapsed"
                        );

                    } else {

                        localStorage.setItem(
                            "inventory_admin_sidebar",
                            "expanded"
                        );

                    }

                }

            }
        );

    }


    /* =====================================================
       RESTORE SIDEBAR
    ====================================================== */

    function restoreSidebar() {

        if (window.innerWidth > 991) {

            const state =
                localStorage.getItem(
                    "inventory_admin_sidebar"
                );


            if (state === "collapsed") {

                body.classList.add(
                    "admin-sidebar-collapsed"
                );

            } else {

                body.classList.remove(
                    "admin-sidebar-collapsed"
                );

            }

        } else {

            body.classList.remove(
                "admin-sidebar-collapsed"
            );

        }

    }


    restoreSidebar();


    /* =====================================================
       MOBILE OPEN
    ====================================================== */

    if (mobileButton) {

        mobileButton.addEventListener(
            "click",
            function () {

                if (window.innerWidth <= 991) {

                    body.classList.toggle(
                        "admin-sidebar-mobile-open"
                    );


                    const icon =
                        mobileButton.querySelector(
                            "i"
                        );


                    if (icon) {

                        if (
                            body.classList.contains(
                                "admin-sidebar-mobile-open"
                            )
                        ) {

                            icon.className =
                                "bi bi-x-lg";

                        } else {

                            icon.className =
                                "bi bi-list";

                        }

                    }

                }

            }
        );

    }


    /* =====================================================
       CLOSE OVERLAY
    ====================================================== */

    if (overlay) {

        overlay.addEventListener(
            "click",
            function () {

                body.classList.remove(
                    "admin-sidebar-mobile-open"
                );


                const icon =
                    mobileButton?.querySelector(
                        "i"
                    );


                if (icon) {

                    icon.className =
                        "bi bi-list";

                }

            }
        );

    }


    /* =====================================================
       CLOSE AFTER MENU CLICK
    ====================================================== */

    const links =
        sidebar.querySelectorAll("a");


    links.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                if (window.innerWidth <= 991) {

                    body.classList.remove(
                        "admin-sidebar-mobile-open"
                    );


                    const icon =
                        mobileButton?.querySelector(
                            "i"
                        );


                    if (icon) {

                        icon.className =
                            "bi bi-list";

                    }

                }

            }
        );

    });


    /* =====================================================
       RESIZE
    ====================================================== */

    let previousWidth =
        window.innerWidth;


    window.addEventListener(
        "resize",
        function () {

            const currentWidth =
                window.innerWidth;


            /* =============================================
               DESKTOP → MOBILE
            ============================================== */

            if (
                previousWidth > 991 &&
                currentWidth <= 991
            ) {

                body.classList.remove(
                    "admin-sidebar-mobile-open"
                );

                body.classList.remove(
                    "admin-sidebar-collapsed"
                );

            }


            /* =============================================
               MOBILE → DESKTOP
            ============================================== */

            if (
                previousWidth <= 991 &&
                currentWidth > 991
            ) {

                body.classList.remove(
                    "admin-sidebar-mobile-open"
                );

                restoreSidebar();

            }


            previousWidth =
                currentWidth;

        }
    );

});

</script>


</body>

</html>