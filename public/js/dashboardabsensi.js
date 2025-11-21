var baseurl = () => {
    let baseurl = document.querySelector("meta[name=baseurl]");
    return baseurl ? baseurl.content : null;
};
baseurl = baseurl();

var segment = () => {
    let segment = document.querySelector("meta[name=segment]");
    return segment ? segment.content : null;
};
segment = segment();

var fullsegment = () => {
    let fullsegment = document.querySelector("meta[name=fullsegment]");
    return fullsegment ? fullsegment.content : null;
};
fullsegment = fullsegment();

var mainTableId = `#${segment}table`;
var dtAdd;
var dt;

var popUp;
var dataPopUp;

var initData = (function () {
    var tr = document.createElement("tr");
    var tablename = `${fullsegment.split('/').at(1)}table`;
    // alert(tablename)
    var centerColumn = [0, 3, 4];
    var wrapColumn = [1, 4, 5, 6, 7];
    var dateInput = ["tanggal"];

    var tables = function (params, action) {
        dt = $(`#${tablename}`).DataTable({
            processing: true,
            serverSide: true,
            select: {
                style: "os",
                selector: "td:first-child",
                className: "row-selected",
            },
            dom:
                "<'row mb-3'<'col-sm-12 d-flex justify-content-end'B>>" + // tombol di kanan atas
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="ki-duotone ki-export fs-3"></i> <span class="ms-1">Export</span>',
                    className: 'btn btn-sm btn-light-primary fw-bold',
                    buttons: [
                        {
                            extend: 'copyHtml5',
                            text: '<i class="ki-duotone ki-copy fs-3"></i> Copy',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: ':not(:last-child)' // ❌ jangan ekspor kolom terakhir
                            }
                        },
                        {
                            extend: 'csvHtml5',
                            text: '<i class="ki-duotone ki-file fs-3"></i> CSV',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="ki-duotone ki-file-spreadsheet fs-3"></i> Excel',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="ki-duotone ki-file-pdf fs-3"></i> PDF',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            },
                            orientation: 'landscape', // optional biar lebar muat semua
                            pageSize: 'A4'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i> Print',
                            className: 'dropdown-item',
                            title: '-',
                            customize: function (win) {
                                const $body = $(win.document.body);

                                // 🔧 Reset style
                                $body.css({
                                    'font-family': 'Arial, sans-serif',
                                    'font-size': '12px',
                                    'color': '#000',
                                    'margin': '20px'
                                });

                                // 🧾 Header profesional dengan logo + identitas
                                $body.prepend(`
            <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:20px;">
                <div style="display:flex; align-items:center;">
                    <img src="https://103.76.15.27/attendance/public/assets/images/kahap.png" alt="Logo" style="width:80px; margin-right:15px;">
                    <div>
                        <h2 style="margin:0; font-size:20px; font-weight:bold;">PT KAHAPTEX</h2>
                        <p style="margin:3px 0 0 0; font-size:11px; line-height:1.4;">
                            Kp Kedep 16962 Tlajung Udik West Java<br>
                            Telp: +62 21 8676267 
                        </p>
                    </div>
                </div>
            </div>

            <div style="text-align:center; margin-bottom:15px;">
                <h4 style="margin:0; font-size:15px; text-transform:uppercase;">Laporan absensi</h4>
                <p style="margin:3px 0 0 0; font-size:11px;">Dicetak: ${new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</p>
                <hr style="border:1px solid #000; width:95%; margin-top:8px;">
            </div>
        `);

                                // 💅 Styling tabel
                                $body.find('table')
                                    .addClass('compact')
                                    .css({
                                        'font-size': '12px',
                                        'width': '100%',
                                        'border-collapse': 'collapse',
                                        'margin-top': '10px'
                                    });

                                $body.find('table thead th').css({
                                    'border': '1px solid #000',
                                    'text-align': 'center',
                                    'padding': '6px',
                                    'background-color': '#f2f2f2',
                                    'font-weight': 'bold'
                                });

                                $body.find('table tbody td').css({
                                    'border': '1px solid #000',
                                    'padding': '6px'
                                });

                                // 🚫 Sembunyikan kolom "Action" di print
                                $body.find('table thead th:last-child, table tbody td:last-child').hide();

                                // ✍️ Footer tanda tangan profesional
                                $body.append(`
            <div style="width:100%; margin-top:70px; display:flex; justify-content:flex-end;">
                <div style="text-align:center; width:220px;">
                    <p style="margin:0;">Disetujui,</p>
                    <div style="height:80px;"></div>
                    <p style="margin:0; font-weight:bold; text-decoration:underline;">Ttd</p>
                    <p style="margin:0;"HRD</p>
                </div>
            </div>
        `);

                                // ⚙️ CSS tambahan untuk print A4 rapi
                                const style = `
            <style>
                @page { size: A4; margin: 20mm; }
                body { -webkit-print-color-adjust: exact !important; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
            </style>
        `;
                                $body.append(style);
                            }
                        }
                    ]
                }

            ],

            initComplete: function () {
                this.api()
                    .columns()
                    .every(function () {
                        let column = this;
                        let title = column.header().textContent;
                        let reference = document.querySelector(`#${tablename} thead tr`);
                        let thead = document.querySelector(`#${tablename} thead`);
                        let titleArray = ["option"];

                        // Create input element
                        $(column.header()).addClass('align-middle')
                        if (centerColumn.includes($(column.header()).index())) {
                            $(column.header()).addClass('text-center px-6');
                        }
                        if (!wrapColumn.includes($(column.header()).index())) {
                            $(column.header()).addClass('text-nowrap');
                        }
                        this.th = document.createElement("th");
                        this.th.setAttribute("class", "px-1");

                        if (!titleArray.includes(title.toLowerCase())) {
                            let input = document.createElement("input");
                            let newTitle = `column-${title
                                .split(" ")
                                .join("")
                                .toLowerCase()}`;
                            input.setAttribute(
                                "class",
                                "form-control form-control-sm trigger-column"
                            );
                            input.setAttribute("id", `${newTitle}`);

                            if (
                                title.toLowerCase() == "no" ||
                                title.toLowerCase() == "actions" ||
                                title.toLowerCase() == "#"
                            ) {
                                input.setAttribute("style", "visibility:hidden");
                            }

                            input.placeholder = title;
                            this.th.appendChild(input);
                            tr.appendChild(this.th);

                            thead.insertBefore(tr, reference);
                            if (dateInput.includes(title.toLowerCase())) {
                                flatpickr(input, {
                                    mode: "range",
                                    dateFormat: "Y-m-d",
                                    allowInput: true,
                                    locale: {
                                        rangeSeparator: " s.d ",
                                        firstDayOfWeek: 1
                                    },
                                    onChange: function (selectedDates, dateStr, instance) {
                                        if (selectedDates.length === 2) {
                                            const startDate = instance.formatDate(selectedDates[0], "Y-m-d");
                                            const endDate = instance.formatDate(selectedDates[1], "Y-m-d");
                                            input.value = `${startDate} s.d ${endDate}`;
                                        }
                                    },
                                    onClose: function (selectedDates, dateStr, instance) {
                                        if (selectedDates.length === 2) {
                                            const startDate = instance.formatDate(selectedDates[0], "Y-m-d");
                                            const endDate = instance.formatDate(selectedDates[1], "Y-m-d");
                                            column.search(`${startDate}_${endDate}`).draw();
                                        } else {
                                            input.value = "";
                                            column.search("").draw();
                                        }
                                    },
                                    onReady: function (selectedDates, dateStr, instance) {
                                        instance.calendarContainer.classList.add("shadow-lg", "rounded");
                                    }
                                });

                                // kalau user hapus manual pakai keyboard
                                input.addEventListener("input", function (e) {
                                    if (e.target.value === "") {
                                        column.search("").draw();
                                    }
                                });
                            }



                            input.addEventListener("keydown", (e) => {
                                // e.preventDefault();
                                if (e.key == "Enter" && input.value == "") {
                                    column.search(input.value).draw();
                                }
                            });

                            input.addEventListener("change", (e) => {
                                e.preventDefault();
                                column.search(input.value).draw();
                            });
                        } else {
                            let labelName = title
                                .toLowerCase()
                                .split(" ")
                                .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
                                .join(" ");
                            let idColumn = title.toLowerCase().split(" ");
                            idColumn.length > 1
                                ? (idColumn = idColumn.join(""))
                                : (idColumn = title.toLowerCase());

                            let selectValue = document.createElement("select");
                            selectValue.setAttribute(
                                "class",
                                "form-select form-select-primary form-select-sm"
                            );
                            selectValue.setAttribute("id", `column-${idColumn}`);
                            selectValue.dataset.control = "select2";

                            selectValue.dataset.placeholder = `Pilih ${labelName}...`;
                            selectValue.setAttribute("aria-label", `Pilih ${labelName}`);
                            selectValue.setAttribute("aria-hidden", "false");

                            let option = document.createElement("option");
                            $(option).val("");
                            $(option).html(`Pilih ${labelName}...`);

                            $(selectValue).append(option);
                            $(selectValue).attr('data-allow-clear', true);

                            if (title.toLowerCase() == "grup barang") {
                                if (window.data.result.grupbarang) {
                                    let grupbarang = window.data.result.grupbarang;
                                    grupbarang.forEach((e) => {
                                        let option = document.createElement("option");

                                        option.value = `${e.gb_name}`;
                                        option.innerHTML = `${e.gb_name}`;

                                        selectValue.append(option);
                                    });
                                }
                            }

                            if (title.toLowerCase() == "jenis barang") {
                                if (window.data.result.jenisbarang) {
                                    let jenisbarang = window.data.result.jenisbarang;

                                    jenisbarang.forEach((e) => {
                                        let option = document.createElement("option");
                                        option.value = `${e.jp_name}_${e.jp_id}`;
                                        option.innerHTML = `${e.jp_name}`;

                                        if (jpId.length == 1) {
                                            if (jpId[0] == e.jp_id) {
                                                option.selected = true;
                                            }
                                        }
                                        selectValue.append(option);
                                    });
                                }
                            }

                            if (title.toLowerCase() == "status") {
                                for (let i = 0; i < 3; i++) {
                                    let option = document.createElement("option");

                                    if (i == 0)
                                        option.value = 'DRAF';
                                    if (i == 1)
                                        option.value = 'AKTIF';
                                    if (i == 2)
                                        option.value = 'TIDAK AKTIF';

                                    $(option).html($(option).val());
                                    selectValue.append(option);
                                }
                            }

                            if (title.toLowerCase() == "option") {
                                // console.log(selectValue);
                                $(selectValue).addClass('pe-none d-none');
                            }

                            this.th.appendChild(selectValue);
                            tr.appendChild(this.th);

                            thead.insertBefore(tr, reference);

                            $(selectValue).select2();

                            $(`#column-${idColumn}`).on("change", (e) => {
                                column.search(e.target.value).draw();
                            });
                        }
                    });
            },
            ajax: {
                // "url": "http://192.168.4.152/erp/erp/setup/master/itemtype/fetchData",
                url: `${baseurl}/${fullsegment}/fetchdata`,
                type: "POST",
                data: function (d) {
                    d._token = document.querySelector('meta[name="csrf-token"]').content,
                        d.data = params;
                },
                dataSrc: function (json) {
                    window.permission = null;

                    if (json.permission.length > 0) {
                        window.permission = json.permission[0];
                    }

                    window.data = json;
                    window.select = json.data;

                    if (json.result) {
                        window.productiontype = json.result.productiontype
                            ? json.result.productiontype
                            : null;
                    }

                    if (json.draw > 1) {
                        document.querySelector('meta[name="csrf-token"]').content =
                            json.token;
                        let inputToken = document.querySelectorAll(
                            'input[name*="_token"]'
                        );
                        Array.from(inputToken).map((item) => {
                            item.value = json.token;
                        });
                    }

                    return json.data;
                },
            },
            columns: [
                { data: null },
                { data: "tgl_absen" },
                { data: "jam" },
                {
                    data: "status",
                    render: function (data, type, row) {
                        if (data === 'masuk') {
                            return '<span class="badge badge-light-danger fw-bold">Masuk</span>';
                        } else if (data === 'pulang') {
                            return '<span class="badge badge-light-success fw-bold">Pulang</span>';
                        } else {
                            return '<span class="badge badge-light-secondary fw-bold">' + data + '</span>';
                        }
                    }
                },
                { data: "karyawan_name" },
                { data: "msn_name" },
                { data: "company" },
                { data: "verification_method" },
                { data: null }
            ],
            columnDefs: [
                {
                    targets: "_all",
                    class: "align-middle",
                },
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    target: 0,
                    className: "text-center px-6",
                    render: function (data, type, row, meta) {
                        if (fullsegment.split("/").at(-1) == "select") {
                            let checked = "";
                            if (window.selectedData) {
                                checked = window.selectedData.includes(row.sjd_id)
                                    ? "checked"
                                    : "";
                            }

                            return `<div class="form-check form-check-sm form-check-custom form-check-solid">
                                                <input class="form-check-input" onchange="updateChecked(this)" type="checkbox" value="${row.sjd_id}" ${checked}>
                                            </div>`;
                        } else {
                            return `<p class='m-0'>${meta.row + 1}</p>`;
                        }
                    },
                },
                {
                    targets: -1,
                    render: function (data, type, row, meta) {
                        let actionValue = `<button type="button" class="btn btn-sm btn-primary"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-start">
                        Option
                        </button>`;

                        let menuValue = false;

                        if (!menuValue) {
                            actionValue = `${actionValue} <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-4"
                            data-kt-menu="true">`;
                            menuValue = true;
                        }

                        if (window.permission.can_edit) {
                            actionValue = `${actionValue} <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">
                                    Edit
                                </a>
                            </div>`
                        }

                        if (window.permission.can_delete) {
                            actionValue = `${actionValue} <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">
                                    Delete
                                </a>
                            </div>`
                        }
                        actionValue = `${actionValue} </div>`;

                        return actionValue;
                    },
                },
                {
                    targets: centerColumn,
                    orderable: false,
                    className: "text-center px-6"
                }
            ],
            createdRow: function (row, data, dataIndex) {
                centerColumn.forEach(e => {
                    $(`td:eq(${e})`, row).addClass('text-center px-6');
                });
            }
        });

        dt.on("draw", function () {
            KTMenu.createInstances();
        });
    };

    var initToggleToolbar = () => {
        const container = document.querySelector(`#${tablename}`);
        const checkboxes = container.querySelectorAll('[type="checkbox"]');

        checkboxes.forEach((c) => {
            c.addEventListener("click", function () {
                setTimeout(function () {
                    toggleToolbars();
                }, 50);
            });
        });
    };

    var toggleToolbars = () => {
        const container = document.querySelector(`#${tablename}`);
        const toolbarBase = document.querySelector(
            '[data-kt-docs-table-toolbar="base"]'
        );
        const toolbarSelected = document.querySelector(
            '[data-kt-docs-table-toolbar="selected"]'
        );
        const selectedCount = document.querySelector(
            '[data-kt-docs-table-select="selected_count"]'
        );

        const allCheckboxes = container.querySelectorAll('tbody [type="checkbox"]');

        let checkedState = false;
        let count = 0;

        allCheckboxes.forEach((c) => {
            if (c.checked) {
                checkedState = true;
                count++;
            }
        });

        if (checkedState) {
            selectedCount.innerHTML = count;
            toolbarBase.classList.add("d-none");
            toolbarSelected.classList.remove("d-none");
        } else {
            toolbarBase.classList.remove("d-none");
            toolbarSelected.classList.add("d-none");
        }
    };

    $(`#${tablename}`).on("xhr.dt", function (e, setting, json, xhr) {

    });

    return {
        init: function (params, action = null) {
            // initToggleToolbar();
            tables(params, action);
        },
    };
})();

$(document).ready(function () {
    initData.init();
});