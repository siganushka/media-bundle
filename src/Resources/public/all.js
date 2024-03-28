const handleMediaUpload = (el, input, channel, accept) => {
  if (el.classList.contains('media-uploaded')
    || el.classList.contains('media-loading')
    || el.classList.contains('media-disabled')) {
    return false
  }

  const view = el.querySelector('.media-view')
  const data = document.getElementById(input)
  const file = document.createElement('input')
        file.setAttribute('type', 'file')
        file.setAttribute('accept', accept)
        file.click()

  file.addEventListener('change', (event) => {
    const files = event.target.files
    if (!files.length) {
      return false
    }

    const formData = new FormData()
    formData.append('channel', channel)
    formData.append('file', files[0])

    // Add loading before send
    el.classList.add('media-loading')

    // @see https://github.com/JakeChampion/fetch
    fetch('/api/media', {
      method: 'POST',
      body: formData,
    }).then(async response => {
      const json = await response.json()
      if (response.status >= 200 && response.status < 300) {
        view.innerHTML = json.image ? `<img src="${json.url}" />` : `<small>${json.name}</small>`
        data.value = json.hash
        el.classList.add('media-uploaded')
      } else {
        throw new Error(json.detail || json.message || response.statusText)
      }
    }).catch(err => {
      alert(err)
    }).finally(() => {
      el.classList.remove('media-loading')
    })
  }, false)
}

const handleMediaRemove = (el, input, confirmationText) => {
  if (false === confirm(confirmationText)) return false

  const wrap = el.closest('.media-wrap')
  const view = wrap.querySelector('.media-view')
  const data = document.getElementById(input)

  wrap.classList.remove('media-uploaded')
  view.replaceChildren()
  data.removeAttribute('value')
}
