/*-- v1.1.0.1.202201262120, from home --*/

function get_date_picker()
{
    // function to act as a class
    function Datepicker() { }

    // gets called once before the renderer is used
    Datepicker.prototype.init = function (params) {
        // create the cell
        this.eInput = document.createElement('input');
        this.eInput.setAttribute('type', 'date');
        this.eInput.value = params.value;
        this.eInput.classList.add('ag-input');
        this.eInput.style.width = '100%';
        this.eInput.style.height = '100%';
    };

    // gets called once when grid ready to insert the element
    Datepicker.prototype.getGui = function () {
        return this.eInput;
    };

    // focus and select can be done after the gui is attached
    Datepicker.prototype.afterGuiAttached = function () {
        this.eInput.focus();
        this.eInput.select();
    };

    // returns the new value after editing
    Datepicker.prototype.getValue = function () {
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
    };

    return Datepicker;
}
