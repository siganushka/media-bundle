const siganushkaMedia = async () => {
  const handle = async (element) => {
    const input = element.querySelector('input[type=file]')
    const data = element.querySelector('input[type=text]')
    const view = element.querySelector('.media-preview')
    const remove = element.querySelector('.btn-close')

    input.addEventListener('change', async (event) => {
      const files = event.target.files
      if (!files.length) return false

      const formData = new FormData()
      formData.append('channel', input.getAttribute('channel'))
      formData.append('file', files[0])

      // Add loading before send
      element.classList.add('media-loading')

      fetch('/api/media', {
        method: 'POST',
        body: formData,
        headers: { Accept: 'application/json' },
      }).then(async response => {
        const json = await response.json()
        return (response.status >= 200 && response.status < 300)
          ? Promise.resolve(json)
          : Promise.reject(json.detail || json.message || response.statusText)
      }).then(res => {
        view.innerHTML = res.image ? `<img src="${res.url}" />` : `<small>${res.name}</small>`
        data.value = res.hash
        input.disabled = true
        element.classList.add('media-uploaded')
      }).catch(err => {
        alert(err)
      }).finally(() => {
        // Reset input file
        input.value = ''
        element.classList.remove('media-loading')
      })
    })

    remove.addEventListener('click', (event) => {
      event.preventDefault()
      const { confirmationText } = event.target.dataset
      if (confirmationText && confirm(confirmationText)) {
        view.replaceChildren()
        data.value = ''
        input.disabled = false
        element.classList.remove('media-uploaded')
      }
    })
  }

  const elements = document.querySelectorAll('.media-uploader')
  elements.forEach(element => handle(element))
}

// Native event
document.addEventListener('DOMContentLoaded', siganushkaMedia)
// Hotwire turbo event
document.addEventListener('turbo:render', siganushkaMedia)
