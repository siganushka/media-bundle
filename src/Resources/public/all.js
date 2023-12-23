const handleMediaUrlUpload = function (event, target, channel) {
  if (!event.files.length) {
    return false
  }

  const $target = $(target)
  const $label = $(event).closest('.media-url-label')
  const $preview = $label.children('.media-url-preview')

  const formData = new FormData()
    formData.append('channel', channel)
    formData.append('file', event.files[0])

  $.ajax({
    url: '/api/media',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    beforeSend: function() {
      $label
        .removeClass('media-url-uploaded')
        .addClass('media-url-loading')
    }
  })
  .done(function(res) {
    $target.val(res.reference)
    $preview.attr('src', res.reference)
    $label.addClass('media-url-uploaded')
  })
  .fail(function(err) {
    alert(err.responseJSON.message || err.statusText)
  })
  .always(function() {
    $label.removeClass('media-url-loading')
    // reset input file
    $(event).wrap('<form>').closest('form').get(0).reset()
    $(event).unwrap()
  })
}

const handleMediaUrlRemove = function (event, target) {
  if (!confirm('确定删除码？')) {
    return false
  }

  const $target = $(target)
  const $label = $(event).closest('.media-url-label')
  const $preview = $label.children('.media-url-preview')

  $target.val('')
  $preview.attr('src', '')
  $label.removeClass('media-url-uploaded')
}
