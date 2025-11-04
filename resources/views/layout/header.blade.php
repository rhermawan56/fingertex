{{-- Header Section --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title', 'Dashboard')</title>
    <meta name="description" content="The most advanced Bootstrap Admin Theme on Themeforest trusted by 94,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue &amp; Laravel versions. Grab your copy now and get life-time updates for free." />
    <meta name="keywords" content="Metronic, bootstrap, bootstrap 5, Angular, VueJs, React, Laravel, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="segment" content="{{request()->segment(1)}}">
    <meta name="fullsegment" content="{{implode('/', request()->segments())}}">
    <meta name="baseurl" content="{{url('/')}}">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <meta charset="utf-8" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Metronic - Bootstrap 5 HTML, VueJS, React, Angular &amp; Laravel Admin Dashboard Theme" />
    <meta property="og:url" content="https://keenthemes.com/metronic" />
    <meta property="og:site_name" content="Keenthemes | Metronic" />
    <link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Page Vendor Stylesheets(used by this page)-->
    <link href="{{ asset('metronic/assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Page Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<script>
function filterSidebarMenu(query) {
    // Always hide all spinner(s) when filtering
    var spinners = document.querySelectorAll('[data-kt-search-element="spinner"]');
    if (spinners.length) {
        spinners.forEach(function(spinner) {
            if (spinner && spinner.classList) spinner.classList.add('d-none');
        });
    }
    query = query.toLowerCase();
    // Sidebar menu filtering & collect matches for dropdown
    var sidebarMenuItems = document.querySelectorAll('#kt_aside_menu .menu-item');
    var sidebarVisible = false;
    var sidebarMatches = [];
    sidebarMenuItems.forEach(function(menuItem) {
        if (!menuItem) return;
        var links = menuItem.querySelectorAll('.menu-link');
        var itemVisible = false;
        links.forEach(function(link) {
            if (!link) return;
            var text = link.textContent || link.innerText;
            if (text && text.toLowerCase().indexOf(query) > -1) {
                link.style.display = '';
                itemVisible = true;
                sidebarVisible = true;
                // Collect for dropdown result
                sidebarMatches.push({
                    text: text.trim(),
                    href: link.getAttribute('href') || '#'
                });
            } else {
                link.style.display = 'none';
            }
        });
        // Handle submenu expand/collapse
        var submenu = menuItem.querySelector('.menu-sub');
        if (submenu) {
            // If any child link is visible, show submenu and mark parent as active
            var anyChildVisible = Array.from(submenu.querySelectorAll('.menu-link')).some(function(link) {
                return link && link.style.display !== 'none';
            });
            if (anyChildVisible) {
                if (submenu.classList) submenu.classList.add('show');
                if (menuItem.classList) menuItem.classList.add('here');
                menuItem.style.display = '';
                sidebarVisible = true;
            } else {
                if (submenu.classList) submenu.classList.remove('show');
                if (menuItem.classList) menuItem.classList.remove('here');
                // Hide parent if it doesn't match query
                if (!itemVisible) menuItem.style.display = 'none';
            }
        } else {
            // No submenu, just show/hide item
            if (itemVisible) {
                menuItem.style.display = '';
            } else {
                menuItem.style.display = 'none';
            }
        }
    });
    // Recently Searched/Main menu filtering
    var mainMenuItems = document.querySelectorAll('[data-kt-search-element="main"] .d-flex.align-items-center.mb-5');
    var mainVisible = false;
    mainMenuItems.forEach(function(item) {
        var text = item.textContent || item.innerText;
        if (text && text.toLowerCase().indexOf(query) > -1) {
            item.style.display = '';
            mainVisible = true;
        } else {
            item.style.display = 'none';
        }
    });
    // Render sidebar matches in dropdown search result (unique only)
    var resultsBox = document.querySelector('[data-kt-search-element="results"]');
    if (resultsBox) {
        if (query.length > 0 && sidebarMatches.length > 0) {
            // Remove duplicates by text+href
            var unique = {};
            var uniqueMatches = [];
            sidebarMatches.forEach(function(match) {
                var key = match.text + '|' + match.href;
                if (!unique[key]) {
                    unique[key] = true;
                    uniqueMatches.push(match);
                }
            });
            resultsBox.classList.remove('d-none');
            var html = '<div class="scroll-y mh-200px mh-lg-350px">';
            uniqueMatches.forEach(function(match) {
                html += '<a href="' + match.href + '" class="d-flex text-dark text-hover-primary align-items-center mb-5">';
                html += '<div class="symbol symbol-40px me-4"><span class="symbol-label bg-light"><i class="bi bi-list"></i></span></div>';
                html += '<div class="d-flex flex-column justify-content-start fw-bold">';
                html += '<span class="fs-6 fw-bold">' + match.text + '</span>';
                html += '</div></a>';
            });
            html += '</div>';
            resultsBox.innerHTML = html;
        } else {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
        }
    }
    // Show/hide empty state
    var emptyState = document.querySelector('[data-kt-search-element="empty"]');
    if (emptyState) {
        if (!sidebarVisible && !mainVisible && query.length > 0) {
            emptyState.classList.remove('d-none');
        } else {
            emptyState.classList.add('d-none');
        }
    }
}
</script>
<!--end::Head-->
