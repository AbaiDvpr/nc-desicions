function fill(text, name) {
  return String(text || '').replaceAll('{name}', name || 'Проект')
}

function Cover({ template, facts }) {
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
      {sections.map((section, index) => (
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
