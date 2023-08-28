/**
 * copy bibtex code button
 */
function copyToClipboard(element)
{
  let $temp = $('<input>');
  $(element).prev('.bibtex-code').parent().append($temp);
  $temp.val($(element).prev('.bibtex-code').text()).select();
  document.execCommand("copy");
  $temp.remove();

}

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

/** even listener **
 ******************/

$('a.bibtextoggle').click(function ()
    {
        $(this).parent('div').next('div').next('div').slideToggle();
        return false;
    }
);

// submit form when different sorting is selected (radio button)
$('input.bibtex-form-radio[type=radio]').on('change', function() {
    $(this).closest("form").submit();
});

/** check if permission to copy to clipboard */
navigator.permissions.query({ name: "clipboard-write" }).then((result) => {
  if (result.state != "granted" && result.state != "prompt") {
    // no permission: deactivate copy button
    //$('.uniol_bibtex_copy').hide();
  }
});

/** event listener for copy bibtex event */
$('.uniol_bibtex_copy').click(function(){
  copyToClipboard($(this));
});

