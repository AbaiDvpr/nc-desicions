import { MOSAIC_APPENDICES, mosaicOutline } from '../bpMosaic.js'

const YEAR = new Date().getFullYear()

function fill(text, name) {
  return String(text || '').replaceAll('{name}', name || 'Проект')
}

function MosaicCover({ facts }) {
  return (
    <header className="bp-doc__cover bp-doc__cover--mosaic">
      <span className="bp-doc__mosaic-year">{YEAR}</span>
      <span className="bp-doc__mosaic-tiles" aria-hidden="true">
        <i />
        <i />
        <i />
        <i />
        <i />
        <i />
        <i />
        <i />
      </span>
      <div className="bp-doc__mosaic-panel">
        <div className="bp-doc__kind">Бизнес-план</div>
        <h1>{facts.name}</h1>
      </div>
      <span className="bp-doc__mosaic-dots" aria-hidden="true" />
    </header>
  )
}

function MosaicContents({ outline }) {
  return (
    <section className="bp-doc__section bp-doc__toc">
      <h2>Оглавление</h2>
      <ol>
        {outline.flatMap((part, index) =>
          part.subs.map((sub, at) => (
            <li key={sub}>
              <span>
                {index + 1}.{at + 1}. {sub}
              </span>
            </li>
          )),
        )}
      </ol>
      <p className="bp-doc__toc-note">Номера страниц проставляются в файле Word и PDF.</p>
    </section>
  )
}

function MosaicBody({ outline }) {
  return (
    <>
      {outline.map((part, index) => (
        <section key={part.title} className="bp-doc__section">
          <h2>
            {index + 1}. {part.title.toUpperCase()}
          </h2>
          {part.subs.map((sub, at) => (
            <h3 key={sub} className="bp-doc__sub">
              {index + 1}.{at + 1}. {sub}
            </h3>
          ))}
          <p className="bp-doc__note">{part.note}</p>
        </section>
      ))}
      {MOSAIC_APPENDICES.map((title) => (
        <section key={title} className="bp-doc__section">
          <h2>{title}</h2>
        </section>
      ))}
    </>
  )
}

function stamp() {
  const date = new Date()
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}.${date.getFullYear()}`
}

function GrowthCover({ facts }) {
  const company = facts.company || '—'
  return (
    <header className="bp-doc__cover bp-doc__cover--growth">
      <svg className="bp-doc__growth-art" viewBox="0 0 210 297" aria-hidden="true">
        <defs>
          <marker id="growth-arrow" markerWidth="7" markerHeight="7" refX="6" refY="3.5" orient="auto">
            <polygon points="0,0 7,3.5 0,7" fill="#c4a35a" />
          </marker>
        </defs>
        <polygon points="146,0 210,0 210,71" fill="#102848" />
        <polygon points="184,0 210,0 210,28" fill="#0a1a30" />
        <line x1="146" y1="0" x2="210" y2="71" stroke="#c4a35a" strokeWidth="0.7" />
        <polygon points="0,297 0,262 56,297" fill="#102848" />
        <polygon points="0,297 0,279 29,297" fill="#0a1a30" />
        <line x1="0" y1="262" x2="56" y2="297" stroke="#c4a35a" strokeWidth="0.7" />
        <g fill="#c5d4e2" opacity="0.7">
          <rect x="128" y="168" width="10" height="52" />
          <rect x="140" y="148" width="14" height="72" />
          <rect x="156" y="128" width="16" height="92" />
          <rect x="174" y="156" width="14" height="64" />
          <rect x="190" y="140" width="14" height="80" />
        </g>
        <g>
          <rect x="138" y="214" width="12" height="18" fill="#c4a35a" />
          <rect x="154" y="200" width="12" height="32" fill="#c4a35a" />
          <rect x="170" y="184" width="12" height="48" fill="#c4a35a" />
          <rect x="186" y="164" width="12" height="68" fill="#c4a35a" />
        </g>
        <path d="M128 226 Q168 196 202 158" fill="none" stroke="#c4a35a" strokeWidth="1.1" markerEnd="url(#growth-arrow)" />
        <line x1="62" y1="286" x2="78" y2="286" stroke="#c4a35a" strokeWidth="0.4" />
        <line x1="132" y1="286" x2="148" y2="286" stroke="#c4a35a" strokeWidth="0.4" />
      </svg>
      <div className="bp-doc__growth-brand">
        <strong>Компания</strong>
        <span>{company}</span>
      </div>
      <h1>Бизнес-план</h1>
      <span className="bp-doc__growth-rule" />
      <p className="bp-doc__growth-project">{facts.name}</p>
      <ul className="bp-doc__growth-meta">
        <li>
          <i className="bp-doc__growth-icon bp-doc__growth-icon--building" aria-hidden="true" />
          <span>
            <strong>Компания:</strong>
            {company}
          </span>
        </li>
        <li>
          <i className="bp-doc__growth-icon bp-doc__growth-icon--date" aria-hidden="true" />
          <span>
            <strong>Дата:</strong>
            {stamp()}
          </span>
        </li>
      </ul>
      <p className="bp-doc__growth-foot">Бизнес-план • Конфиденциально</p>
    </header>
  )
}

function Cover({ template, facts }) {
  if (template.cover === 'growth') return <GrowthCover facts={facts} />
  if (template.cover === 'mosaic') return <MosaicCover facts={facts} />
  if (template.cover === 'book') {
    const year = String(facts.period || '').match(/\d{4}/)?.[0] || ''
    return (
      <header className="bp-doc__cover bp-doc__cover--book">
        <div className="bp-doc__brand">
          NC-
          <br />
          CONSULTING
        </div>
        <div className="bp-doc__ring" aria-hidden="true" />
        <h1>
          Бизнес-
          <br />
          план
        </h1>
        <p className="bp-doc__book-name">{facts.name}</p>
        {year && <p className="bp-doc__year">{year}</p>}
        <div className="bp-doc__curves" aria-hidden="true" />
      </header>
    )
  }
  return (
    <header className="bp-doc__cover">
      <div className="bp-doc__brand">NC-Decision</div>
      <div className="bp-doc__kind">Бизнес-план</div>
      <h1>{facts.name}</h1>
      {template.cover === 'investor' && facts.financing && (
        <div className="bp-doc__finance">{facts.financing}</div>
      )}
      <ul className="bp-doc__meta">
        {facts.legal && <li>{facts.legal}</li>}
        {template.cover !== 'investor' && facts.financing && <li>{facts.financing}</li>}
        {facts.period && <li>{facts.period}</li>}
        {facts.loan && <li>{facts.loan}</li>}
      </ul>
    </header>
  )
}

function Facts({ facts }) {
  return (
    <div className="bp-doc__facts">
      <table>
        <tbody>
          <tr>
            <th>Проект</th>
            <td>{facts.name}</td>
          </tr>
          {facts.legal && (
            <tr>
              <th>Форма</th>
              <td>{facts.legal}</td>
            </tr>
          )}
          {facts.financing && (
            <tr>
              <th>Финансирование</th>
              <td>{facts.financing}</td>
            </tr>
          )}
          {facts.period && (
            <tr>
              <th>Период</th>
              <td>{facts.period}</td>
            </tr>
          )}
          {facts.loan && (
            <tr>
              <th>Кредит</th>
              <td>{facts.loan}</td>
            </tr>
          )}
        </tbody>
      </table>
      {facts.products?.length > 0 && (
        <table className="bp-doc__products">
          <thead>
            <tr>
              <th>Позиция</th>
              <th>Объём при полной загрузке</th>
              <th>Цена</th>
            </tr>
          </thead>
          <tbody>
            {facts.products.map((item) => (
              <tr key={item.name}>
                <td>{item.name}</td>
                <td>{item.volume}</td>
                <td>{item.price}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

export default function BpDocument({ template, facts }) {
  const sections = template.sections || []
  const outline = template.cover === 'mosaic' ? mosaicOutline(facts.financing) : null
  return (
    <article className={`bp-doc bp-doc--${template.cover || 'classic'}`} style={{ '--bp': template.accent || '#1e3a5f' }}>
      <Cover template={template} facts={facts} />
      {template.cover === 'book' && (
        <section className="bp-doc__section bp-doc__glance">
          <h2>
            <span>01</span>
            Ключевые показатели
          </h2>
          <div className="bp-doc__tiles">
            {[facts.name, facts.legal, facts.financing, facts.period].filter(Boolean).map((item) => (
              <div key={item}>{item}</div>
            ))}
          </div>
          <div className="bp-doc__bars" aria-hidden="true">
            <span style={{ height: '42%' }} />
            <span style={{ height: '68%' }} />
            <span style={{ height: '84%' }} />
            <span style={{ height: '100%' }} />
          </div>
          <p>В файле на этом месте график выручки по годам и доли финансирования. Цифры берутся из модели.</p>
        </section>
      )}
      {outline && <MosaicContents outline={outline} />}
      {outline && <MosaicBody outline={outline} />}
      {!outline && sections.map((section, index) => (
        <section key={section.key || index} className="bp-doc__section">
          <h2>
            <span>{String(index + 1).padStart(2, '0')}</span>
            {section.title}
          </h2>
          {section.source === 'facts' && !section.sample ? (
            <Facts facts={facts} />
          ) : (
            <>
              {(fill(section.sample, facts.name) || 'Здесь будет текст этого раздела.')
                .split(/\n+/)
                .map((paragraph) => (
                  <p key={paragraph}>{paragraph}</p>
                ))}
            </>
          )}
        </section>
      ))}
    </article>
  )
}
