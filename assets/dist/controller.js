import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['file', 'data', 'view']

  static values = {
    url: String,
  }

  change(event) {
    const files = event.target.files
    if (!files.length) return false

    const formData = new FormData()
    formData.append('file', files[0])

    this.element.classList.add('siganushka-media-loading')

    fetch(this.urlValue, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    }).then(async response => {
      const json = await response.json()
      return response.ok ? Promise.resolve(json) : Promise.reject(json.detail || response.statusText)
    }).then(res => {
      event.target.disabled = true
      this.dataTarget.value = res.hash
      this.element.classList.add('siganushka-media-uploaded')
      if (res.mime.startsWith('image/')) {
        this.viewTarget.innerHTML = `<img src="${res.url}" />`
      } else if (res.mime.startsWith('video/')) {
        this.viewTarget.innerHTML = `<video src="${res.url}" controls />`
      } else {
        this.viewTarget.innerHTML = res.name
      }
    }).catch(err => {
      alert(err)
      event.target.disabled = false
    }).finally(() => {
      event.target.value = ''
      this.element.classList.remove('siganushka-media-loading')
    })
  }

  remove(event) {
    event.preventDefault()
    this.fileTarget.disabled = false
    this.dataTarget.value = ''
    this.viewTarget.replaceChildren()
    this.element.classList.remove('siganushka-media-uploaded')
  }
}
