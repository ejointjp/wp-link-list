// let jQuery = require('jquery')
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
        required: '必ず入力してください',
        url: 'URLの形式が正しくありません'
      }
    }
  })
})
