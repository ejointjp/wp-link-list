require('jquery-validation')

jQuery(function () {
  jQuery('.post-type-links #post').validate({
    rules: {
      'wpll-url': {
        required: true,
        url: true
      }
    },
    messages: {
      'wpll-url': {
        required: 'URL is required. URLは必ず入力してください。',
        url: 'URL format is invalid. URLの形式が正しくありません。'
      }
    }
  })
})
