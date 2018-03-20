$(document).ready(function()
{    
    function userNameValid($userName)
    {
        return /^[0-9a-z]{3,20}$/i.test($userName);
    }
    
    function passWordValid($passWord)
    {
        return /[A-Z]/.test($passWord) && 
               /[a-z]/.test($passWord) && 
               /[0-9]/.test($passWord) &&
               $passWord.length >= 8;

        return /^[0-9a-z]{3,20}$/i.test($userName);
    }

    function checkAndReflectStatus(value, field, validator, extra)
    {
        if(value.length === 0)
        {
            field.removeClass('error'); 
            field.removeClass('success');
        }
        else if(validator(value, extra))
        {
            field.addClass('success');
            field.removeClass('error');
        }
        else
        {
            field.addClass('error');
            field.removeClass('success');
        }
    }

    var $userNameField = $('#username_field');
    var $passWordField = $('#password_field');

    $('#username').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $userNameField, userNameValid);
    });

    $('#password').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $passWordField, passWordValid);
    });

});