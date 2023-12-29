const handleMediaUpload = function (event, input, channel, accept) {
  const $label = $(event.currentTarget)
  if ($label.hasClass('media-success')
    || $label.hasClass('media-loading')
    || $label.hasClass('media-disabled')) {
    return false
  }

  const $input = $(input)
  const $preview = $label.children('.media-preview')

  const $file = $('<input>', { type: 'file', accept }).click()
  $file.on('change', function (event) {
    const files = event.target.files
    if (!files.length) {
      return false
    }

    const formData = new FormData()
    formData.append('channel', channel)
    formData.append('file', files[0])

    $.ajax({
      url: '/api/media',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: function() {
        $label.addClass('media-loading')
      }
    })
    .done(function(res) {
      $input.val(res.hash)
      $preview.attr('src', res.url)
      $label.addClass('media-success')
    })
    .fail(function(err) {
      alert(err.responseJSON.message || err.statusText)
    })
    .always(function() {
      $label.removeClass('media-loading')
    })
  })
}

const handleMediaRemove = function (event, input) {
  event.stopPropagation()
  if (confirm('确定删除码？')) {
    const $input = $(input)
    const $label = $(event.currentTarget).closest('.media-wrap')
    const $preview = $label.children('.media-preview')

    $input.removeAttr('value')
    $preview.removeAttr('src')
    $label.removeClass('media-success')
  }
}
