var ZERO_KEYCODE = 48;
var NINE_KEYCODE = 57;
var BACKSPACE_KEYCODE = 8;
var TAB_KEYCODE = 9;
var ENTER_KEYCODE = 13;

function FilterTimeInput(e)
{   
    if (e.keyCode == ENTER_KEYCODE)
    {
        if ($('#mainaction').length)
        {
            $('#mainaction').click();
        }
    }
    if (e.keyCode == BACKSPACE_KEYCODE || e.keyCode == TAB_KEYCODE) //backspace or tab, do nothing
    {
    }
    else if (e.keyCode >= ZERO_KEYCODE && e.keyCode <= NINE_KEYCODE)
    {
        var current_val = $(this).val();
        console.log(current_val.length);
        if (current_val.length < 5)
        {
            if (current_val.length == 0) // nothing in
            {
                if (e.keyCode > ZERO_KEYCODE + 2)
                {
                    $(this).val(current_val + '0');
                }
            }
            
            if (current_val.length == 2) // backspaced before
            {
                $(this).val(current_val + ':');
                current_val = $(this).val();
            }
            
            if (current_val.length == 3) //two digits + :
            {
                if (e.keyCode > ZERO_KEYCODE + 5)
                {
                    $(this).val(current_val + '0');
                }
            }
        }
        else
        {
            return false; // dont need any more characters
        }
    }
    else
    {
        return false;
    }
}

function TimeInputPostProcess(e)
{
    var current_val = $(this).val();
    if (current_val.length == 2 && e.keyCode != BACKSPACE_KEYCODE)
    {
        $(this).val($(this).val() + ':');
    }
    else if (e.keyCode == BACKSPACE_KEYCODE && current_val.length == 3) // after backspace
    {
        $(this).val($(this).val().slice(0, -1));
    }
}