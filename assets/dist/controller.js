import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['file', 'data', 'view']

  static values = {
    url: String,
    channel: String,
    confirm: String,
  }

  change(event) {
    console.log('change...', event)

    const files = event.target.files
    if (!files.length) return false

    const formData = new FormData()
    formData.append('channel', this.channelValue)
    formData.append('file', files[0])

    // Add loading before send
    this.element.classList.add('media-loading')

    fetch(this.urlValue, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    }).then(async response => {
      const json = await response.json()
      return response.ok
        ? Promise.resolve(json)
        : Promise.reject(json.detail || json.message || response.statusText)
    }).then(res => {
      event.target.disabled = true
      this.dataTarget.value = res.hash
      this.viewTarget.innerHTML = res.image ? `<img src="${res.url}" />` : `<small>${res.name}</small>`
      this.element.classList.add('media-uploaded')
    }).catch(err => {
      alert(err)
    }).finally(() => {
      // Reset input file
      event.target.value = ''
      this.element.classList.remove('media-loading')
    })
  }

  remove(event) {
    event.preventDefault()
    if (confirm(this.confirmValue)) {
      this.fileTarget.disabled = false
      this.dataTarget.value = ''
      this.viewTarget.replaceChildren()
      this.element.classList.remove('media-uploaded')
    }
  }
}
