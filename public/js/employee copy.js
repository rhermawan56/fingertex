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
    var tablename = `${segment}table`;
    var centerColumn = [0, 3, 4, 5];
    var falseorder = [0, 4];
    var wrapColumn = [];
    var dateInput = ["tanggal spp"];
    // console.log(`${baseurl}/${segment}/fetchData`);

    var tables = function (params, action) {
        dt = $(`#${tablename}`).DataTable({
            processing: true,
            serverSide: true,
            select: {
                style: "os",
                selector: "td:first-child",
                className: "row-selected",
            },
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
                                $(input).daterangepicker({
                                    opens: "left",
                                    linkedCalendars: false,
                                    autoUpdateInput: false,
                                });

                                $(input).on("apply.daterangepicker", function (ev, picker) {
                                    let startDate = picker.startDate.format("YYYY-MM-DD");
                                    let endDate = picker.endDate.format("YYYY-MM-DD");

                                    column.search(`${startDate}_${endDate}`).draw();
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
                    window.permission = json.permission[0];
                    window.data = json;
                    window.select = json.data;

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
                { data: "kar_id" },
                { data: "nama" },
                { data: "machine" },
                { data: "cloud_id" },
                { data: null },
            ],
            columnDefs: [
                {
                    targets: "_all",
                    class: "align-middle text-nowrap",
                },
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return (meta.row + 1) + parseInt(window.data.start);
                    }
                },
                {
                    targets: [-3],
                    render: function (data, type, row, meta) {
                        let span = '';
                        data.forEach(e => {
                            span = `${span} <span class="badge badge-info">${e}</span><br>`
                        });

                        return span;
                    }
                },
                {
                    targets: [-2],
                    class: 'text-center px-6',
                    render: function (data, type, row, meta) {
                        let span = '';
                        data.forEach(e => {
                            span = `${span} <span class="badge badge-info">${e}</span><br>`
                        });

                        return span;
                    }
                },
                {
                    targets: -1,
                    render: function (data, type, row, meta) {
                        let actionValue = `<button type="button" class="btn btn-sm btn-primary"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-start">
                        Option
                        </button>`;


                        if (window.permission) {
                            console.log(window.permission);
                            let menuValue = false;

                            if (!menuValue) {
                                actionValue = `${actionValue} <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-4"
                                data-kt-menu="true">`;
                                menuValue = true;
                            }

                            if (window.permission.can_edit) {
                                actionValue = `${actionValue} <div class="menu-item px-3">
                                    <a href="${baseurl}/${fullsegment}/${row.kar_id}/edit" class="menu-link px-3">
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
                        }

                        return actionValue;
                    },
                },
                {
                    targets: falseorder,
                    orderable: false
                }
            ],
            createdRow: function (row, data, dataIndex) {
                centerColumn.map(e => $(`td:eq(${e})`, row).addClass('text-center px-6'));
                wrapColumn.map(e => $(`td:eq(${e})`, row).removeClass('text-nowrap'));
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
            initToggleToolbar();
            tables(params, action);
        },
    };
})();

function alerts2(params) {
    Swal.fire({
        title: "Confirmation",
        text: "Are you sure you want to continue this process?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Save",
    }).then((result) => {
        if (result.isConfirmed) {
            let form = $(params).closest('form');
            $(form).submit();
        }
    });
}

$(document).ready(function () {
    if (fullsegment.split('/').at(-1) == 'employee') {
        initData.init();
    }
});