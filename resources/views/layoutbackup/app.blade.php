@include('layout.header')
<body>
    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            @include('layout.sidebar')
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @yield('content')
                @include('layout.footer')
            </div>
        </div>
    </div>
</body>
</html>