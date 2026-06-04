<template>
  <div class="aia" :class="panelClass">

    <!-- ── État IDLE : bouton d'invitation ── -->
    <div v-if="state === 'idle'" class="aia__invite">
      <div class="aia__invite-left">
        <div class="aia__invite-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 2a1 1 0 0 1 .894.553l1.91 3.826 4.226.614a1 1 0 0 1 .554 1.706l-3.058 2.98.722 4.208a1 1 0 0 1-1.45 1.054L12 14.77l-3.798 1.995a1 1 0 0 1-1.45-1.054l.721-4.208L4.416 8.7a1 1 0 0 1 .554-1.706l4.226-.614L11.106 2.553A1 1 0 0 1 12 2z" fill="currentColor" opacity=".25"/>
            <circle cx="12" cy="12" r="3" fill="currentColor"/>
            <path d="M12 5v1M12 18v1M5 12H4M20 12h-1M7.05 7.05l-.707-.707M17.657 17.657l-.707-.707M7.05 16.95l-.707.707M17.657 6.343l-.707.707" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="aia__invite-text">
          <p class="aia__invite-title">AI Solution Assistant</p>
          <p class="aia__invite-sub">Obtenez une recommandation de correction générée par l'IA pour ce ticket</p>
        </div>
      </div>
      <button class="aia__cta" @click="startAnalysis">
        <svg viewBox="0 0 20 20" fill="none" class="aia__cta-icon">
          <path d="M10 2l1.5 4.5L16 8l-4.5 1.5L10 14l-1.5-4.5L4 8l4.5-1.5z" fill="currentColor"/>
        </svg>
        Générer une solution
      </button>
    </div>

    <!-- ── État RUNNING : progression animée ── -->
    <div v-else-if="state === 'running'" class="aia__running">
      <div class="aia__running-header">
        <div class="aia__pulse-ring">
          <div class="aia__pulse-dot"></div>
        </div>
        <div>
          <p class="aia__running-title">Analyse en cours…</p>
          <p class="aia__running-sub">{{ currentStep?.label }}</p>
        </div>
        <div class="aia__running-pct">{{ Math.round(progress) }}%</div>
      </div>

      <div class="aia__bar-track">
        <div class="aia__bar-fill" :style="{ width: progress + '%' }"></div>
      </div>

      <div class="aia__steps">
        <div
          v-for="(step, i) in steps"
          :key="i"
          class="aia__step"
          :class="{
            'aia__step--done':    i < currentStepIdx,
            'aia__step--active':  i === currentStepIdx,
            'aia__step--pending': i > currentStepIdx,
          }"
        >
          <div class="aia__step-indicator">
            <svg v-if="i < currentStepIdx" viewBox="0 0 14 14" fill="none">
              <path d="M2.5 7l3.5 3.5 5.5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else-if="i === currentStepIdx" viewBox="0 0 14 14" fill="none" class="aia__spin">
              <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="2" stroke-dasharray="14 16" stroke-linecap="round"/>
            </svg>
            <span v-else class="aia__step-num">{{ i + 1 }}</span>
          </div>
          <span class="aia__step-label">{{ step.label }}</span>
        </div>
      </div>
    </div>

    <!-- ── État DONE : résultat ── -->
    <div v-else-if="state === 'done'" class="aia__result">
      <div class="aia__result-header">
        <div class="aia__result-icon">
          <svg viewBox="0 0 20 20" fill="none">
            <path d="M10 2l1.5 4.5L16 8l-4.5 1.5L10 14l-1.5-4.5L4 8l4.5-1.5z" fill="currentColor"/>
          </svg>
        </div>
        <div class="aia__result-meta">
          <p class="aia__result-title">Recommandation IA</p>
          <p class="aia__result-sub">Basée sur le titre, la description et la catégorie du ticket</p>
        </div>
        <button class="aia__regen" @click="startAnalysis" :disabled="regenLoading" title="Régénérer">
          <svg viewBox="0 0 18 18" fill="none" :class="{ 'aia__spin': regenLoading }">
            <path d="M14.5 9A5.5 5.5 0 1 1 9 3.5h2.5M12 1l-1 2.5 2.5 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Régénérer
        </button>
      </div>

      <div class="aia__solution-body">
        <div class="aia__solution-line" v-for="(line, i) in solutionLines" :key="i" :style="{ animationDelay: i * 0.06 + 's' }">
          {{ line }}
        </div>
      </div>

      <div class="aia__result-footer">
        <svg viewBox="0 0 16 16" fill="none" class="aia__footer-icon">
          <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/>
          <path d="M8 7v4M8 5.3v-.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Cette suggestion est générée automatiquement. Utilisez votre jugement pour l'appliquer.
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import api from '../../services/api';

const props = defineProps({
  ticketId:  { type: Number, required: true },
  solution:  { type: String, default: null  },
});

const state          = ref('idle');
const progress       = ref(0);
const currentStepIdx = ref(0);
const regenLoading   = ref(false);
const displayedSolution = ref(props.solution || '');

const steps = [
  { label: 'Lecture du ticket et de sa description…',     duration: 900  },
  { label: 'Analyse des détails et du contexte…',          duration: 1000 },
  { label: 'Recherche de solutions possibles…',            duration: 1100 },
  { label: 'Évaluation de la meilleure approche…',         duration: 900  },
  { label: 'Génération de la recommandation finale…',      duration: 800  },
];

const currentStep = computed(() => steps[currentStepIdx.value]);

const panelClass = computed(() => ({
  'aia--idle':    state.value === 'idle',
  'aia--running': state.value === 'running',
  'aia--done':    state.value === 'done',
}));

const solutionLines = computed(() =>
  (displayedSolution.value || '').split(/\n+/).filter(l => l.trim())
);

let progressInterval = null;
let totalDuration = steps.reduce((s, st) => s + st.duration, 0);

const startAnalysis = async () => {
  if (state.value === 'running') return;
  state.value = 'running';
  regenLoading.value = false;
  progress.value = 0;
  currentStepIdx.value = 0;

  // API call runs in parallel with the animation
  const apiCall = api.post(`/tickets/${props.ticketId}/analyze-ai`);

  // Animate steps sequentially
  let elapsed = 0;
  for (let i = 0; i < steps.length; i++) {
    currentStepIdx.value = i;
    const stepDuration = steps[i].duration;
    const startPct = (elapsed / totalDuration) * 100;
    const endPct   = ((elapsed + stepDuration) / totalDuration) * 100;

    await animateProgress(startPct, endPct, stepDuration);
    elapsed += stepDuration;
  }

  // Wait for API if still pending
  try {
    const res = await apiCall;
    displayedSolution.value = res.data?.ticket?.solution_ia || res.data?.ai?.solution_ia || props.solution || '';
  } catch {
    displayedSolution.value = props.solution || '';
  }

  progress.value = 100;
  state.value = 'done';
};

const animateProgress = (from, to, duration) => {
  return new Promise(resolve => {
    const start = performance.now();
    const tick = (now) => {
      const t = Math.min((now - start) / duration, 1);
      const ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
      progress.value = from + (to - from) * ease;
      if (t < 1) requestAnimationFrame(tick);
      else resolve();
    };
    requestAnimationFrame(tick);
  });
};
</script>

<style scoped>
/* ── Shell ── */
.aia {
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  overflow: hidden;
  transition: border-color 0.3s, box-shadow 0.3s;
}
.aia--running {
  border-color: var(--color-brand-300);
  box-shadow: 0 0 0 3px var(--color-brand-100);
}
.aia--done {
  border-color: var(--color-brand-200);
  box-shadow: 0 4px 24px rgba(37, 99, 235, 0.08);
}

/* ── IDLE ── */
.aia__invite {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.125rem 1.25rem;
}
.aia__invite-left {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  min-width: 0;
}
.aia__invite-icon {
  flex-shrink: 0;
  width: 36px; height: 36px;
  border-radius: 10px;
  background: var(--color-brand-subtle);
  border: 1px solid var(--color-brand-muted);
  color: var(--color-brand);
  display: flex; align-items: center; justify-content: center;
}
.aia__invite-icon svg { width: 20px; height: 20px; }
.aia__invite-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  line-height: 1.3;
}
.aia__invite-sub {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
  line-height: 1.4;
  margin-top: 2px;
}
.aia__cta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 1rem;
  height: 2.25rem;
  border-radius: var(--radius-md);
  background: var(--color-brand);
  color: var(--color-on-brand);
  border: none;
  cursor: pointer;
  font-family: var(--font-family-sans);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  white-space: nowrap;
  flex-shrink: 0;
  transition: background 0.15s, transform 0.1s;
}
.aia__cta:hover    { background: var(--color-brand-hover); }
.aia__cta:active   { transform: scale(0.97); }
.aia__cta-icon     { width: 14px; height: 14px; flex-shrink: 0; }

/* ── RUNNING ── */
.aia__running {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.aia__running-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}
.aia__pulse-ring {
  flex-shrink: 0;
  width: 32px; height: 32px;
  border-radius: 50%;
  background: var(--color-brand-subtle);
  border: 2px solid var(--color-brand-muted);
  display: flex; align-items: center; justify-content: center;
  animation: ring-pulse 1.8s ease-in-out infinite;
}
.aia__pulse-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--color-brand);
  animation: dot-pulse 1.8s ease-in-out infinite;
}
.aia__running-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  line-height: 1.2;
}
.aia__running-sub {
  font-size: var(--font-size-xs);
  color: var(--color-brand);
  line-height: 1.3;
  margin-top: 2px;
  min-height: 1.2em;
  transition: opacity 0.2s;
}
.aia__running-pct {
  margin-left: auto;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
  color: var(--color-brand);
  min-width: 36px;
  text-align: right;
}

.aia__bar-track {
  height: 5px;
  background: var(--color-neutral-100);
  border-radius: 99px;
  overflow: hidden;
}
.aia__bar-fill {
  height: 100%;
  background: var(--color-brand);
  border-radius: 99px;
  transition: width 0.12s linear;
}

.aia__steps {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.aia__step {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--color-border-subtle);
}
.aia__step:last-child { border-bottom: none; }

.aia__step-indicator {
  flex-shrink: 0;
  width: 22px; height: 22px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
  font-weight: 700;
  transition: background 0.2s, color 0.2s;
}
.aia__step--done .aia__step-indicator {
  background: var(--color-success-100);
  color: var(--color-success-600);
}
.aia__step--done .aia__step-indicator svg { width: 13px; height: 13px; }
.aia__step--active .aia__step-indicator {
  background: var(--color-brand-subtle);
  color: var(--color-brand);
}
.aia__step--active .aia__step-indicator svg { width: 13px; height: 13px; }
.aia__step--pending .aia__step-indicator {
  background: var(--color-bg-subtle);
  color: var(--color-text-muted);
}

.aia__step-num { font-size: 10px; font-weight: 700; }

.aia__step-label {
  font-size: var(--font-size-xs);
  transition: color 0.2s, font-weight 0.2s;
}
.aia__step--done    .aia__step-label { color: var(--color-text-muted); }
.aia__step--active  .aia__step-label { color: var(--color-brand-700); font-weight: var(--font-weight-semibold); }
.aia__step--pending .aia__step-label { color: var(--color-text-muted); }

.aia__spin { animation: spin-anim 0.9s linear infinite; }

/* ── DONE ── */
.aia__result { display: flex; flex-direction: column; }

.aia__result-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border-subtle);
  background: var(--color-bg-subtle);
}
.aia__result-icon {
  flex-shrink: 0;
  width: 34px; height: 34px;
  border-radius: 10px;
  background: var(--color-brand-subtle);
  border: 1px solid var(--color-brand-muted);
  color: var(--color-brand);
  display: flex; align-items: center; justify-content: center;
}
.aia__result-icon svg { width: 17px; height: 17px; }
.aia__result-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  line-height: 1.2;
}
.aia__result-sub {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
  margin-top: 2px;
}
.aia__result-meta { flex: 1; min-width: 0; }

.aia__regen {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0 0.75rem;
  height: 1.875rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  cursor: pointer;
  font-family: var(--font-family-sans);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  white-space: nowrap;
  flex-shrink: 0;
  transition: all 0.15s;
}
.aia__regen:hover:not(:disabled) {
  border-color: var(--color-brand-300);
  color: var(--color-brand);
  background: var(--color-brand-subtle);
}
.aia__regen:disabled { opacity: 0.5; cursor: not-allowed; }
.aia__regen svg { width: 13px; height: 13px; }

.aia__solution-body {
  padding: 1.125rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.aia__solution-line {
  font-size: var(--font-size-sm);
  line-height: var(--line-height-relaxed);
  color: var(--color-text-primary);
  opacity: 0;
  transform: translateY(5px);
  animation: line-in 0.35s ease forwards;
}

.aia__result-footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: var(--color-bg-subtle);
  border-top: 1px solid var(--color-border-subtle);
  font-size: 11px;
  color: var(--color-text-muted);
  font-style: italic;
}
.aia__footer-icon { width: 13px; height: 13px; flex-shrink: 0; color: var(--color-text-muted); }

/* ── Keyframes ── */
@keyframes spin-anim  { to { transform: rotate(360deg); } }
@keyframes ring-pulse { 0%,100%{ box-shadow: 0 0 0 0 rgba(37,99,235,0.3); } 50%{ box-shadow: 0 0 0 6px rgba(37,99,235,0); } }
@keyframes dot-pulse  { 0%,100%{ transform: scale(1); opacity: 1; } 50%{ transform: scale(0.75); opacity: 0.6; } }
@keyframes line-in    { to { opacity: 1; transform: translateY(0); } }
</style>
