$(document).ready(function()
{    
    var apiTypes = ['trivia', 'math', 'date', 'year'];

    $('#sound_smart').on('click', function(e)
    {
        e.preventDefault();
        $.get('//numbersapi.com/random/' + apiTypes[(Math.floor(Math.random() * apiTypes.length))], function(data) 
        {
            $('#content').append(' ' + data + ' ');
            $('#new_post').submit();    
        });
    });
});