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
    var centerColumn = [0];
    var wrapColumn = [1, 4, 5, 6, 7];
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
                        let titleArray = ["grup barang", "jenis barang", "opsi", "status"];

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
                        } else if (title.toLowerCase() !== "opsi") {
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
                url: `${baseurl}/${segment}/fetchdata`,
                type: "POST",
                data: function (d) {
                    d._token = document.querySelector('meta[name="csrf-token"]').content,
                        d.data = params;
                },
                dataSrc: function (json) {
                    //   window.permission = json.permission;
                    window.data = json;
                    window.select = json.data;

                    //   if (json.result) {
                    //     window.productiontype = json.result.productiontype
                    //       ? json.result.productiontype
                    //       : null;
                    //   }

                    if (json.draw > 1) {
                        document.querySelector('meta[name="csrf-token"]').content =
                            json.token;
                        let inputToken = document.querySelectorAll(
                            'input[name*="csrf_test_name"]'
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
                { data: null },
                { data: null },
            ],
            columnDefs: [
                {
                    targets: "_all",
                    orderable: true,
                    class: "align-middle",
                },
                {
                    targets: 0,
                    orderable: false,
                    class: 'text-center px-6',
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                // {
                //   target: 0,
                //   orderable: false,
                //   className: "text-center px-6",
                //   render: function (data, type, row, meta) {
                //     if (fullsegment.split("/").at(-1) == "select") {
                //       let checked = "";
                //       if (window.selectedData) {
                //         checked = window.selectedData.includes(row.sjd_id)
                //           ? "checked"
                //           : "";
                //       }

                //       return `<div class="form-check form-check-sm form-check-custom form-check-solid">
                //                                 <input class="form-check-input" onchange="updateChecked(this)" type="checkbox" value="${row.sjd_id}" ${checked}>
                //                             </div>`;
                //     } else {
                //       return `<p class='m-0'>${meta.row + 1}</p>`;
                //     }
                //   },
                // },
                // {
                //   targets: -2,
                //   orderable: true,
                //   render: function (data, type, row, meta) {
                //     let span = '';

                //     if (row.jobd_status == '0') {
                //       span = `<span class="badge badge-warning">${data}</span>`;
                //     } else if (row.jobd_status == '1') {
                //       span = `<span class="badge badge-success">${data}</span>`;
                //     } else {
                //       span = `<span class="badge badge-danger">${data}</span>`;
                //     }

                //     return span;
                //   }
                // },
                // {
                //   targets: -1,
                //   orderable: false,
                //   className: "text-center px-6",
                //   render: function (data, type, row, meta) {
                //     if (fullsegment.split("/").at(-1) == "select") {
                //       action = action ? action : fullsegment.split("/").at(-2);
                //       this.actionValue = `<button type="button" onclick='selectData("${encodeURIComponent(JSON.stringify(row))}", "${action}")' class="btn btn-sm btn-primary">Pilih</button>`;
                //     } else {
                //       this.actionValue = `<a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Opsi
                //                                                 <span class="svg-icon svg-icon-5 m-0">
                //                                                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                //                                                         <path
                //                                                             d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z"
                //                                                             fill="black" />
                //                                                     </svg>
                //                                                 </span>
                //                                             </a>`;

                //       this.actionValue = `${this.actionValue} <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px py-4" data-kt-menu="true">`;

                //       if (window.permission.view == "Y") {
                //         this.actionValue = `${this.actionValue} <div class="menu-item px-3">
                //                                                         <a href="#" onclick="setrule('view','${row.jobh_id}')" class="menu-link px-3">Lihat</a>
                //                                                     </div>`;
                //       }

                //       if (window.permission.edit == "Y") {
                //         if (row.status == "DRAF") {
                //           this.actionValue = `${this.actionValue} <div class="menu-item px-3">
                //                                                         <a href="#" onclick="setrule('draf','${row.jobh_id}')" class="menu-link px-3">Draf</a>
                //                                                     </div>`;
                //         }

                //         if (row.status == "AKTIF") {
                //           this.actionValue = `${this.actionValue} <div class="menu-item px-3">
                //                                                         <a href="#" onclick="setrule('edit','${row.jobh_id}')" class="menu-link px-3">Edit</a>
                //                                                     </div>`;
                //         }
                //       }

                //       if (window.permission.delete == "Y") {
                //         this.actionValue = `${this.actionValue} <div class="menu-item px-3">
                //                                                     <a href="#" onclick="setrule('delete','${row.jobh_id}')" class="menu-link px-3">Hapus</a>
                //                                                 </div>`;
                //       }
                //     }

                //     return this.actionValue;
                //   },
                // },
            ],
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

$(document).ready(function () {
    initData.init();
});