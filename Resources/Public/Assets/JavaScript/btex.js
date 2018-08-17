$('a.bibtextoggle').click(function ()
    {
        $(this).parent('div').next('div').next('div').slideToggle();
        return false;
    }
);
