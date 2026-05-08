import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static targets = ['item', 'save', 'status']
  static values = { url: String, csrfToken: String }

  connect () {
    this.draggedItem = null
    this.itemTargets.forEach(item => this.#bindItem(item))
  }

  #bindItem (item) {
    item.draggable = true
    item.addEventListener('dragstart', e => this.#onDragStart(e))
    item.addEventListener('dragover', e => this.#onDragOver(e))
    item.addEventListener('drop', e => e.preventDefault())
    item.addEventListener('dragend', () => this.#onDragEnd(item))
  }

  #onDragStart (event) {
    this.draggedItem = event.currentTarget
    event.currentTarget.classList.add('opacity-40')
    event.dataTransfer.effectAllowed = 'move'
  }

  #onDragOver (event) {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
    const target = event.currentTarget
    if (!this.draggedItem || target === this.draggedItem) return
    const rect = target.getBoundingClientRect()
    const insertBefore = event.clientY < rect.top + rect.height / 2
    target.parentNode.insertBefore(this.draggedItem, insertBefore ? target : target.nextSibling)
  }

  #onDragEnd (item) {
    item.classList.remove('opacity-40')
    this.draggedItem = null
  }

  async save () {
    const ids = this.itemTargets.map(item => parseInt(item.dataset.categoryId, 10))
    this.saveTarget.disabled = true

    try {
      const response = await fetch(this.urlValue, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfTokenValue },
        body: JSON.stringify({ ids }),
      })

      if (response.ok) {
        this.#setStatus('Ordre sauvegardé ✓', 'text-emerald-600')
      } else {
        this.#setStatus('Erreur lors de la sauvegarde', 'text-red-600')
      }
    } catch {
      this.#setStatus('Erreur réseau', 'text-red-600')
    } finally {
      this.saveTarget.disabled = false
    }
  }

  #setStatus (message, colorClass) {
    this.statusTarget.textContent = message
    this.statusTarget.className = 'text-xs ' + colorClass
    setTimeout(() => { this.statusTarget.textContent = '' }, 3000)
  }
}
