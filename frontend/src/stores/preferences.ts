import { defineStore } from 'pinia'
import { ref } from 'vue'
import { playMoveSound } from '../audio/moveSound'

const soundKey = 'chess-openings.move-sound'

export const usePreferencesStore = defineStore('preferences', () => {
  let saved: string | null = null
  try {
    saved = localStorage.getItem(soundKey)
  } catch {
    // Private browsing/storage restrictions must not prevent training.
  }
  const soundEnabled = ref(saved !== 'off')

  function toggleSound() {
    soundEnabled.value = !soundEnabled.value
    try {
      localStorage.setItem(soundKey, soundEnabled.value ? 'on' : 'off')
    } catch {
      // The preference still applies for the current page.
    }
  }

  function moveSound() {
    void playMoveSound(() => soundEnabled.value)
  }

  return { soundEnabled, toggleSound, moveSound }
})
