$(function() {
  $('#contactForm input,#contactForm textarea').jqBootstrapValidation({
    preventSubmit: true,
    submitError: function($form, event, errors) {
        // additional error messages or events
    },
    submitSuccess: function($form, event) {
      // Prevent spam click and default submit behaviour
      $('#btnSubmit').attr('disabled', true);
      event.preventDefault();

      // get values from FORM
      var name = $('#name').val();
      var email = $('#email').val();
      var message = $('#message').val();
      var firstName = name; // For Success/Failure Message

      $.ajax({
        url: 'api/v1/contact/send/',
        type: 'POST',
        data: JSON.stringify({
          name: name,
          email: email,
          message: message
        }),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        cache: false,
        success: function() {
          // Enable button & show success message
          $('#success').removeClass('hide');
          $('#btnSubmit').attr('disabled', false);
          $('#contactForm').trigger('reset');
        },
        error: function() {
          $('#error').removeClass('hide');
          $('#contactForm').trigger('reset');
        },
      });
    },
    filter: function() {
        return $(this).is(':visible');
    },
  });

    $('a[data-toggle=\'tab\']').click(function(e) {
        e.preventDefault();
        $(this).tab('show');
    });
});

// When clicking on Full hide fail/success boxes
$('#name').focus(function() {
    $('#success').addClass('hide');
});
