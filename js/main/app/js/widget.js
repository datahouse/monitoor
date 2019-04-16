$(function() {
  function InputData(url, email, hasError) {
    this.url = url;
    this.email = email;
    this.hasError = hasError;
  };

  $('#widget-submit').on('click', function(event) {
    event.preventDefault();
    var $url = $('#widget-url');
    var $email = $('#widget-email');
    var urlPattern = /\(?(?:(http|https|ftp):\/\/)?(?:((?:[^\W\s]|\.|-|[:]{1})+)@{1})?((?:www.)?(?:[^\W\s]|\.|-)+[\.][^\W\s]{2,4}|localhost(?=\/)|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?::(\d*))?([\/]?[^\s\?]*[\/]{1})*(?:\/?([^\s\n\?\[\]\{\}\#]*(?:(?=\.)){1}|[^\s\n\?\[\]\{\}\.\#]*)?([\.]{1}[^\s\?\#]*)?)?(?:\?{1}([^\s\n\#\[\]]*))?([\#][^\s\n]*)?\)?/;
    var emailPattern = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    var $urlErrorsIsEmpty = $url.next().find('.error-empty');
    var $urlErrorsIsInvalid = $url.next().find('.error-invalid');
    var $emailErrorsIsEmpty = $email.next().find('.error-empty');
    var $emailErrorsIsInvalid = $email.next().find('.error-invalid');
    var $formMsgs = $('.widget');
    var $formMsgSuccess = $formMsgs.find('.msg .success');
    var $formMsgErrorDefault = $formMsgs.find('.msg .error .default');
    var $formMsgErrorCustom = $formMsgs.find('.msg .error .custom');

    var hideMsgs = function() {
      hideErrors();
      $formMsgSuccess.hide();
    };

    var hideErrors = function() {
      hideEmailErrors();
      hideUrlErrors();
      $formMsgErrorDefault.hide();
      $formMsgErrorCustom.hide();
    };

    var hideUrlErrors = function() {
      $urlErrorsIsEmpty.hide();
      $urlErrorsIsInvalid.hide();
    };

    var hideEmailErrors = function() {
      $emailErrorsIsEmpty.hide();
      $emailErrorsIsInvalid.hide();
    };

    var validateFields = function() {
      hideMsgs();
      var validateField = function(value, validationPattern, $isEmptyError, $isInvalidError) {
        var isValid = true;
        $isEmptyError.hide();
        $isInvalidError.hide();

        if (value == '') {
          $isEmptyError.show();
          isValid = false;
        } else if (!validationPattern.test(value)) {
          $isInvalidError.show();
          isValid = false;
        }

        return isValid;
      };

      var inputData = new InputData($url.val(), $email.val(), false);
      var hasUrlError = !validateField($url.val(), urlPattern, $urlErrorsIsEmpty, $urlErrorsIsInvalid);
      var hasEmailError = !validateField($email.val(), emailPattern, $emailErrorsIsEmpty, $emailErrorsIsInvalid);
      inputData.hasError = hasUrlError || hasEmailError;

      return inputData;
    };

    var sendRequests = function() {
      $.ajax({
        url: 'api/v1/url/free/de',
        type: 'POST',
        data: JSON.stringify(inputData),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json'
     }).done(function() {
        console.log($formMsgSuccess);
        $formMsgSuccess.show();
      }).fail(function(jqXHR) {
        var response = jqXHR.responseJSON;
        if (response.code === 400 && response.msg.length > 0) {
          var errorEmailPattern = /^Please enter a valid email \(.*\)$/;
          var errorUrlPattern = /^page .* does not exists$/;
          if (errorEmailPattern.test(response.msg[0])) {
            $emailErrorsIsInvalid.show();
          } else if (errorUrlPattern.test(response.msg[0])) {
            $urlErrorsIsInvalid.show();
          } else {
            $formMsgErrorDefault.show();
          }
        } else if (response.code === 403 && response.msg.length > 0) {
          $formMsgErrorCustom.find('strong').text(response.msg[0]);
          $formMsgErrorCustom.show();
        } else {
          $formMsgErrorDefault.show();
        }
      })
    };

    var inputData = validateFields();
    if (!inputData.hasError) {
      sendRequests();
    }

    return false;
  });
});
