<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { Key } from '@lichess-org/chessground/types'
import { t } from '../i18n'
import ChessBoard from '../components/chess/ChessBoard.vue'

/** The Italian Game, precomputed so the home page does not need the rules engine. */
const start = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'
const plies: { san: string; fen: string; lastMove: [Key, Key] }[] = [
  {
    san: 'e4',
    fen: 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1',
    lastMove: ['e2', 'e4'],
  },
  {
    san: 'e5',
    fen: 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2',
    lastMove: ['e7', 'e5'],
  },
  {
    san: 'Nf3',
    fen: 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq - 1 2',
    lastMove: ['g1', 'f3'],
  },
  {
    san: 'Nc6',
    fen: 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3',
    lastMove: ['b8', 'c6'],
  },
  {
    san: 'Bc4',
    fen: 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq - 3 3',
    lastMove: ['f1', 'c4'],
  },
  {
    san: 'Bc5',
    fen: 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4',
    lastMove: ['f8', 'c5'],
  },
]
const shown = ref(plies.length)
const current = computed(() => plies[shown.value - 1])
const pairs = [0, 2, 4].map((index) => ({ number: index / 2 + 1, white: index, black: index + 1 }))
let timer: ReturnType<typeof setTimeout> | undefined

function step() {
  if (shown.value >= plies.length) return
  shown.value++
  timer = setTimeout(step, 900)
}
function playThrough() {
  if (timer) clearTimeout(timer)
  shown.value = 0
  timer = setTimeout(step, 700)
}
onMounted(() => {
  // One orchestrated moment: the line plays once. Reduced motion keeps the final position.
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) playThrough()
})
onBeforeUnmount(() => {
  if (timer) clearTimeout(timer)
})
</script>

<template>
  <main id="main-content" class="page-width home-main">
    <section class="hero" aria-labelledby="hero-title">
      <h1 id="hero-title">{{ t('Learn the line.') }}<br />{{ t('Recognise the variation.') }}</h1>
      <div class="hero-copy">
        <p class="hero-description">
          {{
            t(
              'First, make an opening feel familiar. Then learn to find your way when the moves change. You move your pieces, we reply, and a hint is always one tap away.',
            )
          }}
        </p>
        <div class="hero-actions">
          <RouterLink class="primary-link" to="/learn">{{
            t('Start your first lesson')
          }}</RouterLink>
          <RouterLink class="text-action" to="/openings">{{ t('Look up an opening') }}</RouterLink>
        </div>
      </div>
      <figure class="hero-demo">
        <div class="hero-board">
          <ChessBoard
            :fen="current?.fen ?? start"
            :last-move="current?.lastMove"
            read-only
            :label="t('Italian Game opening position')"
          />
        </div>
        <figcaption class="scoresheet">
          <strong class="scoresheet-title">{{ t('Italian Game') }}</strong>
          <ol :aria-label="t('Italian Game moves')">
            <li v-for="pair in pairs" :key="pair.number">
              <span class="move-number">{{ pair.number }}.</span>
              <span
                v-for="ply in [pair.white, pair.black]"
                :key="ply"
                :class="{ 'is-now': ply === shown - 1, 'is-ahead': ply >= shown }"
                >{{ plies[ply]?.san }}</span
              >
            </li>
          </ol>
          <p>{{ t('Both bishops aim at the square beside the enemy king.') }}</p>
          <button type="button" class="replay-button" @click="playThrough">
            {{ t('Replay the moves') }}
          </button>
        </figcaption>
      </figure>
    </section>
    <section class="approach" aria-labelledby="approach-title">
      <h2 id="approach-title">{{ t('Two steps to a reliable opening') }}</h2>
      <ol class="steps">
        <li>
          <span class="step-number" aria-hidden="true">1</span>
          <div>
            <h3>{{ t('Follow the line') }}</h3>
            <p>
              {{
                t(
                  'Play one precise sequence. We answer each move and explain why it matters, with a hint whenever you need one.',
                )
              }}
            </p>
            <RouterLink to="/learn">{{ t('Start a guided lesson') }}</RouterLink>
          </div>
        </li>
        <li>
          <span class="step-number" aria-hidden="true">2</span>
          <div>
            <h3>{{ t('Find your response') }}</h3>
            <p>
              {{
                t(
                  'Mix the lines you have learned. We choose among their taught replies, and you find the response that fits.',
                )
              }}
            </p>
            <RouterLink to="/learn#variation-training">{{ t('Choose lines to mix') }}</RouterLink>
          </div>
        </li>
      </ol>
    </section>
  </main>
</template>

<style scoped>
.hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 600px);
  grid-template-areas:
    'title title'
    'copy demo';
  gap: 40px 56px;
  padding-block: 48px 72px;
}
.hero h1 {
  grid-area: title;
  font-size: clamp(40px, 5.4vw, 70px);
  line-height: 1;
  letter-spacing: -0.035em;
  font-weight: 850;
  margin: 0;
  text-wrap: wrap;
}
.hero-copy {
  grid-area: copy;
  padding-top: 8px;
}
.hero-description {
  font-size: 19px;
  line-height: 1.55;
  color: var(--pencil);
  max-width: 34em;
  margin-bottom: 32px;
}
.hero-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 28px;
}
.hero-demo {
  grid-area: demo;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 150px;
  gap: 28px;
  align-items: end;
  margin: 0;
}
.hero-board {
  padding: 3px;
}
.scoresheet {
  font-size: 15px;
}
.scoresheet-title {
  display: block;
  font-size: 19px;
  font-weight: 800;
  font-stretch: var(--semi-wide);
  margin-bottom: 10px;
}
.scoresheet ol {
  list-style: none;
  padding: 0;
  margin: 0 0 16px;
  border-top: 2px solid var(--ink);
}
.scoresheet li {
  display: grid;
  grid-template-columns: 26px 1fr 1fr;
  align-items: center;
  min-height: 38px;
  border-bottom: 1px solid var(--rule);
  font-weight: 650;
}
.scoresheet li span {
  justify-self: start;
  padding: 1px 4px;
  transition:
    color 0.2s,
    background-color 0.2s;
}
.move-number {
  color: var(--pencil);
  font-weight: 500;
}
.scoresheet .is-now {
  background: var(--marker);
}
.scoresheet .is-ahead {
  color: #b6bdca;
}
.scoresheet p {
  color: var(--pencil);
  line-height: 1.45;
}
.replay-button {
  min-height: 40px;
  padding-inline: 12px;
  font-size: 14px;
}
.approach {
  padding-block: 48px 24px;
  border-top: 1px solid var(--rule);
}
.approach h2 {
  font-size: clamp(26px, 3vw, 34px);
  margin-bottom: 32px;
}
.steps {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 32px 56px;
}
.steps li {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 22px;
}
.step-number {
  font-size: 64px;
  font-weight: 850;
  font-stretch: var(--wide);
  line-height: 0.82;
  color: var(--ink);
  -webkit-text-stroke: 0;
}
.steps h3 {
  font-size: 22px;
  margin-bottom: 8px;
}
.steps p {
  color: var(--pencil);
  font-size: 17px;
  margin-bottom: 10px;
}
.steps a {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-weight: 650;
}
@media (prefers-reduced-motion: reduce) {
  .scoresheet li span {
    transition: none;
  }
}
@media (max-width: 1000px) {
  .hero {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas:
      'title'
      'copy'
      'demo';
    gap: 28px;
    padding-block: 40px 56px;
  }
  .hero-demo {
    max-width: 640px;
  }
}
@media (max-width: 680px) {
  .hero h1 {
    font-size: clamp(36px, 11vw, 52px);
    font-stretch: var(--semi-wide);
  }
  .hero-description {
    font-size: 17px;
  }
  .hero-demo {
    grid-template-columns: minmax(0, 1fr);
    gap: 20px;
  }
  .scoresheet ol {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    column-gap: 12px;
  }
  .steps {
    grid-template-columns: minmax(0, 1fr);
  }
  .step-number {
    font-size: 48px;
  }
}
</style>
