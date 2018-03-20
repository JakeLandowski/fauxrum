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

    function repeatPassWordValid($passWord, $repeat)
    {
        return $passWord === $repeat;
    }

    function emailValid($email)
    {
        return $email.length <= 50 && 
               /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/igm.test($email)
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

    var $userNameField       = $('#username_field');
    var $emailField          = $('#email_field');
    var $passWordField       = $('#password_field');
    var $repeatPassWordField = $('#repeat_password_field');
    var $passWord = $('#password');
    var $repeatPassWord = $('#repeat_password'); 
    
    $('#username').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $userNameField, userNameValid);
    });

    $('#email').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $emailField, emailValid);
    });

    $('#password').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $passWordField, passWordValid);
        checkAndReflectStatus($(this).val(), $repeatPassWordField, repeatPassWordValid, $repeatPassWord.val());
    });

    $('#repeat_password').on('keyup', function(e)
    {
        checkAndReflectStatus($(this).val(), $repeatPassWordField, repeatPassWordValid, $passWord.val());
    });

});