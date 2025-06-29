(function($){
  $(function(){
    // Confirmación de carga del script
    console.log('CPP Frontend loaded');

    // 1) Abrir formulario de test
    $(document).on('click', '.js-open-test-form', function(e){
      e.preventDefault();
      console.log('js-open-test-form clicked');
      $('#cpp-test-form-container').slideDown();
      $(this).hide();
    });

    // 2) Cerrar formulario de test
    $(document).on('click', '.js-close-test-form', function(e){
      e.preventDefault();
      console.log('js-close-test-form clicked');
      $('#cpp-test-form-container').slideUp();
      $('.js-open-test-form').show();
      var form = document.getElementById('cppTestRequestForm');
      if(form){ form.reset(); }
      $('#cpp-monitor-tests').hide();
      $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
      $('#cpp-monitor-test-value').val('');
    });

    // 3) Cambiar tipo de test (radio)
    $(document).on('change', 'input[name="test_type"]', function(){
      var val = $(this).val();
      console.log('test_type changed to', val);
      if(val === 'monitor'){
        $('#cpp-monitor-tests').slideDown();
      } else {
        $('#cpp-monitor-tests').slideUp();
        $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
        $('#cpp-monitor-test-value').val('');
      }
    });

    // 4) Seleccionar opción Monitor
    $(document).on('click', '.cpp-monitor-option', function(e){
      e.preventDefault();
      var value = $(this).data('value');
      console.log('Monitor option clicked:', value);
      $('.cpp-monitor-option').removeClass('button-primary').addClass('button');
      $(this).removeClass('button').addClass('button-primary');
      $('#cpp-monitor-test-value').val(value);
    });

    // 5) Manejar envío del formulario de test
    $(document).on('submit', '#cppTestRequestForm', function(e){
      console.log('cppTestRequestForm submit');
      var type = $('input[name="test_type"]:checked').val();
      if(type === 'monitor' && !$('#cpp-monitor-test-value').val()){
        e.preventDefault();
        alert(cppData.i18n.selectMonitorTest);
        return false;
      }
      var $submit = $(this).find('input[type="submit"], button[type="submit"]');
      $submit.prop('disabled', true).val(cppData.i18n.creatingTest);
    });

    // 6) Ver resultado de test vía AJAX
    $(document).on('click', '.cpp-btn-view-result', function(e){
      e.preventDefault();
      var testId = $(this).data('id');
      console.log('cpp-btn-view-result clicked:', testId);
      var $container = $('#cpp-result-container');
      $container.html('<p>' + (cppData.i18n.loadingResult || 'Cargando resultado del test...') + '</p>');
      $.post(cppData.ajaxUrl, {
        action: 'cpp_test_action',
        nonce: cppData.nonce,
        test_action: 'get_results',
        test_id: testId
      }).done(function(response){
        if(response.success){
          var html = '<h4>' + (cppData.i18n.resultTitle || 'Resultado') + '</h4>';
          html += '<pre>' + JSON.stringify(response.data.test, null, 2) + '</pre>';
          $container.html(html);
        } else {
          $container.html('<p>' + response.data + '</p>');
        }
      }).fail(function(){
        $container.html('<p>' + (cppData.i18n.errorLoadingResult || 'Error cargando resultado.') + '</p>');
      });
    });

  });
})(jQuery);