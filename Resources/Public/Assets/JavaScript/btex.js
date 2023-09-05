/**
 * copy bibtex code button
 */
function copyToClipboard(element)
{
  let $temp = $('<input>');
  let textCopied = $(element).prev('.bibtex-code code').text();

  $(element).prev('code').append($temp);
  $temp.val(textCopied).select();
  document.execCommand("copy");
  $temp.remove();

  // mark copied
  $(element).find('.uniol_bibtex_copy_text').addClass('uniol_bibtex_copy_text_kopiert');

  // change text to "copied"
  // the following does not work
  $(element).find('.uniol_bibtex_copy_text').hide();
  $(element).find('.uniol_bibtex_copy_text').show();
}

/*
// currently not used
function copyToClipboard2(element)
{
  const textValue = $(element).prev('.bibtex-code').text();
  if (textValue) {
    writeToClipboard(textValue);
  } else {
    // todo: indication of failed
  }
}

function writeToClipboard(textValue)
{
  navigator.permissions.query({ name: "clipboard-write" }).then((result) => {
    if (result.state === "granted" || result.state === "prompt") {
      navigator.clipboard.writeText(textValue);
    } else {
      // todo: indication of failed
    }
  });
}

 */

/**
 * Hide bibtex code
 */
function hideBibtexCode()
{
  $('.uniol_bibtex .bibtex-code').hide();
}

/** on load **
**************/

// hide bibtex code
hideBibtexCode();

/** even listener **
 ******************/

// hide / unhide bibtex code on click
$('a.bibtextoggle').click(function ()
    {
        $(this).parent('div').next('div').next('div.bibtex-code').slideToggle();
        return false;
    }
);

// submit form when different sorting is selected (radio button)
// deprecated: currently not used
$('input.bibtex-form-radio[type=radio]').on('change', function() {
    $(this).closest("form").submit();
});

/** check if permission to copy to clipboard */
// currently not used
/*
navigator.permissions.query({ name: "clipboard-write" }).then((result) => {
  if (result.state != "granted" && result.state != "prompt") {
    // no permission: deactivate copy button
    //$('.uniol_bibtex_copy').hide();
  }
});
 */

/** event listener for copy bibtex code to clipboard */
$('.uniol_bibtex_copy').click(function(){
  copyToClipboard($(this));
});

