<script setup lang="ts">
import { t } from '../i18n'
import ChessBoard from '../components/chess/ChessBoard.vue'

const previewFen = 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4'
</script>

<template>
  <main id="main-content" class="page-width home-main">
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero-copy">
        <span class="eyebrow"
          ><span class="small-dot" /> {{ t('A clearer start to your game') }}</span
        >
        <h1 id="hero-title">
          {{ t('Learn the line.') }}<br /><em>{{ t('Recognise') }}</em
          ><br />{{ t('the variation.') }}
        </h1>
        <p class="hero-description">
          {{
            t(
              'First, make an opening feel familiar. Then learn to find your way when the moves change. Focused practice, one idea at a time.',
            )
          }}
        </p>
        <RouterLink class="primary-link" to="/learn"
          >{{ t('Start your first lesson') }} <span aria-hidden="true">→</span></RouterLink
        >
        <p class="quiet-note">
          {{ t('Beginner friendly. Automatic replies. Hints whenever you need them.') }}
        </p>
      </div>
      <div class="hero-preview">
        <div class="preview-heading">
          <span>{{ t('THE FIRST FEW MOVES') }}</span
          ><span>{{ t('01 / OPENING THEORY') }}</span>
        </div>
        <div class="board-preview-frame">
          <ChessBoard :fen="previewFen" read-only :label="t('Italian Game opening position')" />
        </div>
        <div class="board-caption">
          <span class="caption-marker">↳</span>
          <div>
            <strong>{{ t('Every move has a place.') }}</strong
            ><span>{{ t('Build an understanding of the opening.') }}</span>
          </div>
        </div>
      </div>
    </section>
    <section id="the-approach" class="approach" aria-labelledby="approach-title">
      <div class="section-intro">
        <span class="eyebrow">{{ t('ONE OPENING. TWO WAYS TO LEARN.') }}</span>
        <h2 id="approach-title">{{ t('From familiar moves to confident choices.') }}</h2>
      </div>
      <div class="mode-cards">
        <article class="mode-card">
          <span class="mode-number">01</span>
          <div>
            <h3>{{ t('Follow the line') }}</h3>
            <p>
              {{
                t(
                  'Learn a precise opening sequence, with hints and explanations when you need them.',
                )
              }}
            </p>
            <RouterLink class="mode-label" to="/learn">{{
              t('START A GUIDED LESSON →')
            }}</RouterLink>
          </div>
          <span class="mode-symbol" aria-hidden="true">→</span>
        </article>
        <article class="mode-card">
          <span class="mode-number">02</span>
          <div>
            <h3>{{ t('Find your response') }}</h3>
            <p>
              {{
                t(
                  'Mix the taught lines you choose and practise responding to different supported replies.',
                )
              }}
            </p>
            <RouterLink class="mode-label" to="/learn#variation-training">{{
              t('CHOOSE LINES TO MIX →')
            }}</RouterLink>
          </div>
          <span class="mode-symbol" aria-hidden="true">⑂</span>
        </article>
      </div>
    </section>
  </main>
</template>
