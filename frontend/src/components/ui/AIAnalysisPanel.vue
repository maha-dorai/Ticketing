<template>
  <div class="aip" :class="panelState">

    <!-- Header -->
    <div class="aip__header">
      <div class="aip__brand">
        <div class="aip__brand-icon">
          <svg viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="3" fill="currentColor"/>
            <path d="M10 2v2M10 16v2M2 10h2M16 10h2M4.93 4.93l1.41 1.41M13.66 13.66l1.41 1.41M4.93 15.07l1.41-1.41M13.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <p class="aip__brand-title">AI Analysis</p>
          <p class="aip__brand-sub">{{ statusText }}</p>
        </div>
      </div>
      <div class="aip__header-end">
        <div v-if="!isDone" class="aip__pct">{{ Math.round(progressPct) }}%</div>
        <div class="aip__progress-track">
          <div class="aip__progress-fill" :style="{ width: progressPct + '%' }"></div>
        </div>
        <span v-if="isDone" class="aip__badge-done">
          <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Done
        </span>
      </div>
    </div>

    <!-- Steps -->
    <div class="aip__body" ref="bodyRef">
      <div v-if="visibleLines.length === 0 && !isRunning" class="aip__idle">
        <svg viewBox="0 0 24 24" fill="none" class="aip__idle-icon">
          <rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M8 12h8M8 8h5M8 16h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>Saisissez un titre pour activer l'analyse IA</span>
      </div>

      <div
        v-for="(line, i) in visibleLines"
        :key="i"
        class="aip__step"
        :class="[`aip__step--${line.type}`, { 'aip__step--in': line.visible }]"
      >
        <div class="aip__step-dot">
          <svg v-if="line.type === 'thinking'" viewBox="0 0 16 16" fill="none" class="aip__spin">
            <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="2" stroke-dasharray="16 18" stroke-linecap="round"/>
          </svg>
          <svg v-else-if="line.type === 'result'" viewBox="0 0 16 16" fill="none">
            <path d="M3.5 8.5l3 3 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="line.type === 'info'" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M8 7v4M8 5.5v-.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <svg v-else-if="line.type === 'final'" viewBox="0 0 16 16" fill="none">
            <path d="M8 2l1.4 4.2L14 8l-4.6 1.8L8 14l-1.4-4.2L2 8l4.6-1.8z" fill="currentColor"/>
          </svg>
        </div>
        <div class="aip__step-content">
          <span class="aip__step-text" v-html="line.html"></span>
          <span v-if="line.type === 'thinking'" class="aip__cursor"></span>
        </div>
      </div>
    </div>

    <!-- Results -->
    <div v-if="isDone && result" class="aip__results">
      <div class="aip__result" :class="`aip__result--${priorityClass}`">
        <span class="aip__result-label">Priorité</span>
        <span class="aip__result-value">{{ priorityLabel }}</span>
      </div>
      <div class="aip__result aip__result--cat">
        <span class="aip__result-label">Catégorie</span>
        <span class="aip__result-value">{{ categoryLabel }}</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  result:    { type: Object,  default: null  },
  isRunning: { type: Boolean, default: false },
  triggered: { type: Boolean, default: false },
});

const bodyRef      = ref(null);
const visibleLines = ref([]);
const progressPct  = ref(0);
const isDone       = ref(false);
let animTimeout    = null;
let progressInterval = null;

const PRIORITY_MAP = { BASSE: 'Basse', MOYENNE: 'Moyenne', HAUTE: 'Haute', CRITIQUE: 'Critique' };
const CATEGORY_MAP = {
  BUG: 'Bug', PERFORMANCE: 'Performance', SECURITE: 'Sécurité',
  UI_UX: 'UI / UX', BASE_DE_DONNEES: 'Base de données', API: 'API',
  CONFIGURATION: 'Configuration', AUTRE: 'Autre', NON_CLASSE: 'Non classé',
};

const buildScript = (r) => {
  const pLabel = PRIORITY_MAP[r?.priorite_ia] || r?.priorite_ia || '—';
  const cLabel = CATEGORY_MAP[r?.categorie_ia] || r?.categorie_ia || '—';
  const pCls   = r?.priorite_ia?.toLowerCase() || 'basse';
  return [
    { type: 'thinking', html: 'Lecture du contenu du ticket…',                          delay: 0    },
    { type: 'info',     html: 'Analyse sémantique du titre et de la description',        delay: 600  },
    { type: 'thinking', html: 'Détection du niveau de priorité…',                       delay: 1200 },
    { type: 'info',     html: 'Application des heuristiques de sévérité',                delay: 1750 },
    { type: 'result',   html: `Priorité détectée : <mark class="aip-tag aip-tag--${pCls}">${pLabel}</mark>`, delay: 2400 },
    { type: 'thinking', html: 'Classification de la catégorie…',                         delay: 2900 },
    { type: 'info',     html: 'Comparaison sur 8 vecteurs de catégories',                delay: 3450 },
    { type: 'result',   html: `Catégorie détectée : <mark class="aip-tag aip-tag--cat">${cLabel}</mark>`,    delay: 4100 },
    { type: 'final',    html: 'Analyse terminée — résultats appliqués au formulaire',    delay: 4800 },
  ];
};

const startProgress = () => {
  progressPct.value = 0;
  progressInterval = setInterval(() => {
    if (progressPct.value < 88) progressPct.value += (88 - progressPct.value) * 0.035 + 0.25;
  }, 80);
};
const finishProgress = () => { clearInterval(progressInterval); progressPct.value = 100; };

const runAnimation = (r) => {
  clearAllTimers();
  visibleLines.value = [];
  isDone.value = false;
  startProgress();

  const script = buildScript(r);
  const timers = [];
  script.forEach((step, idx) => {
    const t = setTimeout(() => {
      visibleLines.value = visibleLines.value.filter(l => l.type !== 'thinking');
      const line = { ...step, visible: false };
      visibleLines.value.push(line);
      nextTick(() => { line.visible = true; scrollBottom(); });
      if (idx === script.length - 1) {
        setTimeout(() => { isDone.value = true; finishProgress(); }, 350);
      }
    }, step.delay);
    timers.push(t);
  });
  animTimeout = timers;
};

const clearAllTimers = () => {
  if (Array.isArray(animTimeout)) animTimeout.forEach(clearTimeout);
  clearInterval(progressInterval);
};
const scrollBottom = () => {
  nextTick(() => { if (bodyRef.value) bodyRef.value.scrollTop = bodyRef.value.scrollHeight; });
};

watch(() => props.result, (v) => { if (v) runAnimation(v); }, { deep: true, immediate: true });

watch(() => props.triggered, (val) => {
  if (!val) {
    clearAllTimers();
    visibleLines.value = [];
    isDone.value = false;
    progressPct.value = 0;
  } else if (val && !props.result) {
    clearAllTimers();
    visibleLines.value = [];
    isDone.value = false;
    startProgress();
    const line = { type: 'thinking', html: 'Lecture du contenu du ticket…', visible: false };
    visibleLines.value.push(line);
    nextTick(() => { line.visible = true; });
  }
});

const panelState = computed(() => ({
  'aip--running': props.isRunning || (props.triggered && !isDone.value),
  'aip--done':    isDone.value,
  'aip--idle':    !props.triggered && !isDone.value,
}));

const statusText = computed(() => {
  if (isDone.value) return 'Analyse terminée';
  if (props.isRunning || (props.triggered && !isDone.value)) return 'Analyse en cours…';
  return 'En attente de saisie';
});

const priorityClass = computed(() => props.result?.priorite_ia?.toLowerCase() || 'basse');
const priorityLabel = computed(() => PRIORITY_MAP[props.result?.priorite_ia] || props.result?.priorite_ia || '—');
const categoryLabel = computed(() => CATEGORY_MAP[props.result?.categorie_ia] || props.result?.categorie_ia || '—');
</script>

<style scoped>
/* ── Shell ── */
.aip {
  border-radius: 10px;
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  overflow: hidden;
  transition: border-color 0.25s, box-shadow 0.25s;
}
.aip--running {
  border-color: var(--color-brand-300);
  box-shadow: 0 0 0 3px var(--color-brand-100);
}
.aip--done {
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 3px var(--color-success-100);
}

/* ── Header ── */
.aip__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 14px;
  background: var(--color-bg-subtle);
  border-bottom: 1px solid var(--color-border-subtle);
}
.aip__brand {
  display: flex;
  align-items: center;
  gap: 9px;
}
.aip__brand-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  background: var(--color-brand-subtle);
  border: 1px solid var(--color-brand-muted);
  color: var(--color-brand);
  flex-shrink: 0;
}
.aip--running .aip__brand-icon { animation: icon-pulse 1.8s ease-in-out infinite; }
.aip__brand-icon svg { width: 16px; height: 16px; }
.aip__brand-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1.2;
}
.aip__brand-sub {
  font-size: 11px;
  font-weight: 400;
  color: var(--color-text-muted);
  line-height: 1.2;
  margin-top: 1px;
  transition: color 0.2s;
}
.aip--running .aip__brand-sub { color: var(--color-brand); }
.aip--done    .aip__brand-sub { color: var(--color-success-600); }

.aip__header-end {
  display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.aip__pct {
  font-size: 11px; font-weight: 700;
  color: var(--color-brand);
  min-width: 28px; text-align: right;
}
.aip__progress-track {
  width: 72px; height: 4px;
  background: var(--color-neutral-200);
  border-radius: 99px; overflow: hidden;
}
.aip--done .aip__progress-track { display: none; }
.aip__progress-fill {
  height: 100%;
  background: var(--color-brand);
  border-radius: 99px;
  transition: width 0.35s ease;
}
.aip--done .aip__progress-fill { background: var(--color-success-500); }
.aip__badge-done {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 9px;
  font-size: 11px; font-weight: 600;
  color: var(--color-success-700);
  background: var(--color-success-50);
  border: 1px solid var(--color-success-100);
  border-radius: 99px;
}
.aip__badge-done svg { width: 11px; height: 11px; }

/* ── Body ── */
.aip__body {
  padding: 12px 14px;
  min-height: 80px;
  max-height: 190px;
  overflow-y: auto;
  display: flex; flex-direction: column; gap: 5px;
  scroll-behavior: smooth;
  scrollbar-width: thin;
  scrollbar-color: var(--color-neutral-200) transparent;
}
.aip__body::-webkit-scrollbar { width: 4px; }
.aip__body::-webkit-scrollbar-thumb { background: var(--color-neutral-200); border-radius: 99px; }

/* ── Idle ── */
.aip__idle {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 0;
  font-size: 12px; color: var(--color-text-muted);
}
.aip__idle-icon { width: 18px; height: 18px; color: var(--color-neutral-300); flex-shrink: 0; }

/* ── Steps ── */
.aip__step {
  display: flex; align-items: flex-start; gap: 8px;
  opacity: 0; transform: translateX(-6px);
  transition: opacity 0.25s ease, transform 0.25s ease;
  font-size: 12.5px; line-height: 1.45;
  font-family: var(--font-family-sans);
}
.aip__step--in { opacity: 1; transform: translateX(0); }

.aip__step-dot {
  flex-shrink: 0;
  width: 16px; height: 16px;
  margin-top: 1px;
  display: flex; align-items: center; justify-content: center;
}
.aip__step-dot svg { width: 14px; height: 14px; }

.aip__step-content { flex: 1; display: flex; align-items: baseline; gap: 4px; }
.aip__step-text    { flex: 1; }

/* thinking */
.aip__step--thinking .aip__step-dot  { color: var(--color-brand); }
.aip__step--thinking .aip__step-text { color: var(--color-brand-700); font-weight: 500; }
.aip__spin { animation: spin-anim 0.9s linear infinite; }

/* info */
.aip__step--info .aip__step-dot  { color: var(--color-neutral-300); }
.aip__step--info .aip__step-text { color: var(--color-text-muted); font-size: 11.5px; }

/* result */
.aip__step--result .aip__step-dot  { color: var(--color-success-500); }
.aip__step--result .aip__step-text { color: var(--color-text-primary); font-weight: 500; }

/* final */
.aip__step--final .aip__step-dot  { color: var(--color-warning-500); }
.aip__step--final .aip__step-text { color: var(--color-text-secondary); font-style: italic; }

/* cursor */
.aip__cursor {
  display: inline-block;
  width: 2px; height: 13px;
  background: var(--color-brand);
  border-radius: 1px;
  animation: cursor-blink 0.85s step-end infinite;
  vertical-align: middle;
  margin-left: 3px;
}

/* inline tags inside log text */
:deep(.aip-tag) {
  display: inline-block;
  padding: 0 6px; height: 18px; line-height: 18px;
  border-radius: 4px;
  font-size: 11px; font-weight: 700;
  font-style: normal;
  font-family: var(--font-family-sans);
  letter-spacing: 0.02em;
  vertical-align: middle;
}
:deep(.aip-tag--critique) { background: var(--color-error-50);   color: var(--color-error-700);   border: 1px solid var(--color-error-100);   }
:deep(.aip-tag--haute)    { background: #fff7ed;                  color: #c2410c;                  border: 1px solid #fed7aa;                  }
:deep(.aip-tag--moyenne)  { background: var(--color-warning-50); color: var(--color-warning-700); border: 1px solid var(--color-warning-100); }
:deep(.aip-tag--basse)    { background: var(--color-bg-subtle);  color: var(--color-text-secondary); border: 1px solid var(--color-border);   }
:deep(.aip-tag--cat)      { background: var(--color-brand-subtle); color: var(--color-brand-700); border: 1px solid var(--color-brand-muted); }

/* ── Results footer ── */
.aip__results {
  display: flex; gap: 8px; flex-wrap: wrap;
  padding: 10px 14px;
  border-top: 1px solid var(--color-border-subtle);
  background: var(--color-bg-subtle);
  animation: slide-up 0.3s ease forwards;
}
.aip__result {
  display: flex; flex-direction: column; gap: 1px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  min-width: 90px;
}
.aip__result-label {
  font-size: 10px; font-weight: 600;
  text-transform: uppercase; letter-spacing: 0.07em;
  color: var(--color-text-muted);
}
.aip__result-value {
  font-size: 13px; font-weight: 700;
  color: var(--color-text-primary);
}
.aip__result--critique { border-color: var(--color-error-100);   background: var(--color-error-50);   }
.aip__result--critique .aip__result-value { color: var(--color-error-700);   }
.aip__result--haute    { border-color: #fed7aa; background: #fff7ed; }
.aip__result--haute    .aip__result-value { color: #c2410c; }
.aip__result--moyenne  { border-color: var(--color-warning-100); background: var(--color-warning-50); }
.aip__result--moyenne  .aip__result-value { color: var(--color-warning-700); }
.aip__result--basse    { border-color: var(--color-border);       background: var(--color-bg-subtle);  }
.aip__result--basse    .aip__result-value { color: var(--color-text-secondary); }
.aip__result--cat      { border-color: var(--color-brand-muted);  background: var(--color-brand-subtle); }
.aip__result--cat      .aip__result-value { color: var(--color-brand-700); }

/* ── Keyframes ── */
@keyframes spin-anim    { to { transform: rotate(360deg); } }
@keyframes cursor-blink { 0%,100%{opacity:1} 50%{opacity:0} }
@keyframes icon-pulse   { 0%,100%{opacity:1} 50%{opacity:0.4} }
@keyframes slide-up     { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:translateY(0)} }
</style>