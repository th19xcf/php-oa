<!-- v1.1.1.0.202201131650, from office -->
<!DOCTYPE html>
<html lang="en">

<head>
    <title>JavaScript example</title>
    <meta charSet="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style media="only screen">
        html,
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            -webkit-overflow-scrolling: touch;
        }

        html {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0;
            overflow: auto;
        }

        body {
            padding: 1rem;
            overflow: auto;
        }

        .ui-datepicker-div
        {
            z-index: 9999;
        }
    </style>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/dhtmlx/codebase/suite.css'>
    <script src='<?php base_url(); ?>/dhtmlx/codebase/suite.js'></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.1/jquery.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js">
    </script>
</head>

<body>
    <div id='toolbarbox'></div>
    <div id='popup'></div>
    <script>
        var __basePath = './';
    </script>

    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-grid.css'>
    <link rel='stylesheet' type='text/css' href='<?php base_url(); ?>/ag-grid/dist/styles/ag-theme-alpine.css'>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-locale-cn.js'></script>
    <script src='<?php base_url(); ?>/ag-grid/dist/ag-grid-community.noStyle.js'></script>

    </script>

    <script type='text/javascript'>
        // 生成主菜单栏
        var main_tb = new dhx.Toolbar('toolbarbox', {
            css: 'toobar-class'
        });
        main_tb.data.add({
            id: '修改',
            type: 'button',
            value: '修改'
        });

        const columnDefs = [{
                field: 'athlete'
            },
            //{ field: 'date', editable: true, cellEditor: 'datePicker' },
            {
                field: 'date',
                editable: true,
                cellEditorSelector: cellEditorSelector
            },
            {
                field: 'age',
                maxWidth: 110
            },
            {
                field: 'country'
            },
            {
                field: 'year',
                maxWidth: 120
            },
            {
                field: 'sport'
            },
            {
                field: 'gold'
            },
            {
                field: 'silver'
            },
            {
                field: 'bronze'
            },
            {
                field: 'total'
            },
        ];

        const gridOptions = {
            columnDefs: columnDefs,
            defaultColDef: {
                flex: 1,
                minWidth: 150,
            },
            components: {
                datePicker: getDatePicker(),
            },
        };

        function cellEditorSelector(params) {
            console.log('into cellEditorSelector, params', params);

            /*
            const popup = new dhx.Popup({
              css: "dhx_widget--bordered"
            });

            const timepicker = new dhx.Timepicker();
            var calendar = new dhx.Calendar();

            popup.attach(calendar);
            popup.show('popup');
            */

            return {
                component: 'datePicker',
            };
        }

        function getDatePicker() {
            // function to act as a class
            function Datepicker() {}

            // gets called once before the renderer is used
            Datepicker.prototype.init = function(params) {
                console.log('into init');
                // create the cell
                this.eInput = document.createElement('input');
                this.eInput.value = params.value;
                this.eInput.classList.add('ag-input');
                this.eInput.style.height = '100%';
                this.eInput.style.zIndex = 9999;

                // https://jqueryui.com/datepicker/
                $(this.eInput).datepicker({
                    dateFormat: 'yy/mm/dd',
                });
            };

            // gets called once when grid ready to insert the element
            Datepicker.prototype.getGui = function() {
                console.log('into getGui');
                return this.eInput;
            };

            // focus and select can be done after the gui is attached
            Datepicker.prototype.afterGuiAttached = function() {
                console.log('into afterGuiAttached');
                this.eInput.focus();
                this.eInput.select();
            };

            // returns the new value after editing
            Datepicker.prototype.getValue = function() {
                console.log('into getvalue');
                return this.eInput.value;
            };

            // any cleanup we need to be done here
            Datepicker.prototype.destroy = () => {
                // but this example is simple, no cleanup, we could
                // even leave this method out as it's optional
            };

            // if true, then this editor will appear in a popup
            Datepicker.prototype.isPopup = () => {
                // and we could leave this method out also, false is the default
                return false;
                //return true;
            };

            return Datepicker;
        }


        // setup the grid after the page has finished loading
        document.addEventListener('DOMContentLoaded', () => {
            // 提前生成录入窗口,否则得不到modify_grid
            var win = new dhx.Window({
                title: '操作窗口',
                footer: true,
                //modal: true,
                width: 700,
                height: 500,
                closable: true,
                movable: true
            });

            win.footer.data.add({
                type: 'button',
                id: '清空',
                value: '清空',
                view: 'flat',
                size: 'medium',
                color: 'primary',
            });

            win.footer.events.on('click', function(id) {
                if (id == '清空') {
                    const popup = new dhx.Popup({
                        css: "dhx_widget--bordered"
                    });

                    const timepicker = new dhx.Timepicker();
                    var calendar = new dhx.Calendar();

                    popup.attach(calendar);
                    popup.show('popup');

                }
            });

            var html = '<div id="myGrid" class="ag-theme-alpine" style="width:100%;height:100%;"></div>';
            win.attachHTML(html);
            win.hide();
            var modify_grid_create = false;

            // 工具栏点击
            main_tb.events.on('click', function(id, e) {
                /*
                const popup = new dhx.Popup({
                    css: "dhx_widget--bordered"
                });

                const timepicker = new dhx.Timepicker();
                var calendar = new dhx.Calendar();

                const result = document.getElementById("result");
                timepicker.events.on("change", function (res) {
                    result.value = res;
                });

                popup.attach(calendar);
                popup.show('popup');
                return;
                */

                win.show();
                const gridDiv = document.querySelector('#myGrid');
                new agGrid.Grid(gridDiv, gridOptions);

                fetch('https://www.ag-grid.com/example-assets/olympic-winners.json')
                    .then((response) => response.json())
                    .then((data) => gridOptions.api.setRowData(data));
            });

        });
    </script>
</body>

</html>