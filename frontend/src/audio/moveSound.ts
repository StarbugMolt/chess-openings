// Original, short wooden impact: a soft noise transient and damped body resonances.
// Generated locally, with no downloaded samples or network requests.
let context: AudioContext | undefined
let buffer: AudioBuffer | undefined

function woodenImpact(audio: AudioContext): AudioBuffer {
  const sample = audio.createBuffer(1, Math.ceil(audio.sampleRate * 0.14), audio.sampleRate)
  const data = sample.getChannelData(0)
  let noise = 0
  let softenedNoise = 0
  const smoothing = 1 - Math.exp((-2 * Math.PI * 480) / audio.sampleRate)
  for (let i = 0; i < data.length; i++) {
    const t = i / audio.sampleRate
    noise += smoothing * (Math.random() * 2 - 1 - noise)
    softenedNoise += smoothing * (noise - softenedNoise)
    const attack = Math.min(1, t / 0.002)
    const body =
      Math.sin(2 * Math.PI * 145 * t) * Math.exp(-t * 85) +
      0.3 * Math.sin(2 * Math.PI * 230 * t) * Math.exp(-t * 110)
    data[i] = attack * (0.26 * body + 1.8 * softenedNoise * Math.exp(-t * 120))
  }
  return sample
}

/** Call from a committed move, inside a user gesture. Audio must never delay chess state. */
export async function playMoveSound(isEnabled: () => boolean): Promise<void> {
  if (!isEnabled()) return
  try {
    if (context?.state === 'closed') context = undefined
    if (!context) {
      context = new AudioContext()
      buffer = woodenImpact(context)
    }
    const requested = performance.now()
    if (context.state === 'suspended') await context.resume()
    // A blocked autoplay request must not produce a late sound after another gesture.
    if (!isEnabled() || context.state !== 'running' || performance.now() - requested > 1000) return
    const source = context.createBufferSource()
    source.buffer = buffer ?? null
    source.connect(context.destination)
    source.onended = () => source.disconnect()
    source.start()
  } catch {
    // Unsupported/blocked audio is optional; moves remain usable.
  }
}
