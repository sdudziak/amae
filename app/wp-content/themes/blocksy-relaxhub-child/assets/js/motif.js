(() => {
  const body = document.body
  // Ensure scoping class exists even if theme didn't add it yet
  if (!body.classList.contains('rh-theme')) body.classList.add('rh-theme')

  const motif = document.querySelector('.rh-motif')
  const layers = document.querySelectorAll('.rh-motif__layer')
  const toggle = document.querySelector('.rh-dark-toggle')
  const LS_KEY = 'relaxhub_theme'

  // Dark mode toggle with localStorage
  const applyTheme = mode => {
    if (!mode) {
      body.removeAttribute('data-theme')
      return
    }
    body.setAttribute('data-theme', mode)
    if (toggle) {
      toggle.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false')
    }
  }

  // Initialize theme from storage
  try {
    const stored = localStorage.getItem(LS_KEY)
    if (stored) applyTheme(stored)
  } catch (e) {}

  // Theme toggle handler
  toggle &&
    toggle.addEventListener('click', () => {
      const cur = body.getAttribute('data-theme')
      const next = cur === 'dark' ? 'light' : 'dark'
      applyTheme(next)
      try {
        localStorage.setItem(LS_KEY, next)
      } catch (e) {}
    })

  // Parallax effect (desktop only)
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)')
  const isMobile = () => window.matchMedia('(max-width:768px)').matches

  function onScroll() {
    if (!motif || prefersReduced.matches || isMobile()) return
    const y = window.scrollY || 0
    layers.forEach(el => {
      const s = parseFloat(el.dataset.speed || '0.3')
      el.style.transform = `translate3d(0,${y * s * -0.12}px,0)`
    })
  }

  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', () => requestAnimationFrame(onScroll), {
    passive: true
  })
  onScroll()
})();