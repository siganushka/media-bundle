const handleMediaUpload = function (event, target, channel) {
  const $label = $(event.currentTarget)
  if ($label.hasClass('media-uploaded') || $label.hasClass('media-loading')) {
    return false
  }

  const $target = $(target)
  const $preview = $label.children('.media-preview')

  const $file = $('<input>', { type: 'file' }).click()
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
      $target.val(res.hash)
      $preview.attr('src', res.url)
      $label.addClass('media-uploaded')
    })
    .fail(function(err) {
      alert(err.responseJSON.message || err.statusText)
    })
    .always(function() {
      $label.removeClass('media-loading')
    })
  })
}

const handleMediaRemove = function (event, target) {
  event.stopPropagation()
  if (confirm('确定删除码？')) {
    const $target = $(target)
    const $label = $(event.currentTarget).closest('.media-label')
    const $preview = $label.children('.media-preview')

    $target.removeAttr('value')
    $preview.removeAttr('src')
    $label.removeClass('media-uploaded')
  }
}
