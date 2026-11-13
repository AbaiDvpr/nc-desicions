function Lines({ count = 3, className = '' }) {
  return Array.from({ length: count }, (_, index) => <span key={index} className={`bp-mini__line ${className}`} />)
}

function Body({ kind }) {
  if (kind === 'mosaic') {
    return (
      <>
        <span className="bp-mini__panel">
          <span className="bp-mini__kicker" />
          <span className="bp-mini__title" />
          <span className="bp-mini__title bp-mini__title--short" />
          <Lines count={3} />
        </span>
        <span className="bp-mini__year">2026</span>
        <span className="bp-mini__sq bp-mini__sq--a" />
        <span className="bp-mini__sq bp-mini__sq--b" />
        <span className="bp-mini__sq bp-mini__sq--c" />
        <span className="bp-mini__sq bp-mini__sq--d" />
        <span className="bp-mini__sq bp-mini__sq--e" />
        <span className="bp-mini__sq bp-mini__sq--f" />
        <span className="bp-mini__dots" />
      </>
    )
  }
  if (kind === 'growth') {
    return (
      <>
        <span className="bp-mini__growth-title" />
        <span className="bp-mini__growth-rule" />
        <span className="bp-mini__growth-line" />
        <span className="bp-mini__growth-line bp-mini__growth-line--short" />
        <span className="bp-mini__growth-bar bp-mini__growth-bar--a" />
        <span className="bp-mini__growth-bar bp-mini__growth-bar--b" />
        <span className="bp-mini__growth-bar bp-mini__growth-bar--c" />
        <span className="bp-mini__growth-bar bp-mini__growth-bar--d" />
      </>
    )
  }
  if (kind === 'book') {
    return (
      <>
        <span className="bp-mini__ring" />
        <span className="bp-mini__stack">
          <span className="bp-mini__title" />
          <span className="bp-mini__title bp-mini__title--short" />
          <Lines count={1} />
        </span>
      </>
    )
  }
  if (kind === 'investor') {
    return (
      <>
        <span className="bp-mini__band">
          <span className="bp-mini__kicker" />
          <span className="bp-mini__title" />
          <span className="bp-mini__title bp-mini__title--short" />
        </span>
        <span className="bp-mini__stack bp-mini__stack--low">
          <Lines count={4} />
        </span>
      </>
    )
  }
  return (
    <span className="bp-mini__stack">
      <span className="bp-mini__kicker" />
      <span className="bp-mini__title" />
      <span className="bp-mini__title bp-mini__title--short" />
      <Lines count={3} />
    </span>
  )
}

export default function BpMiniCover({ kind }) {
  return (
    <span className={`bp-mini bp-mini--${kind}`} aria-hidden="true">
      <Body kind={kind} />
    </span>
  )
}
