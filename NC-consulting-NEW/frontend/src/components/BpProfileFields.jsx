import { LIST_LIMIT } from '../bpProfileFields.js'

export function FieldGrid({ groups, data, onChange }) {
  return groups.map((group) => (
    <div className={`bp-profile__grid${group.long || group.wide ? ' is-long' : ''}`} key={group.fields[0].key}>
      {group.fields.map((field) => {
        const props = {
          value: data[field.key] || '',
          maxLength: field.max,
          placeholder: field.placeholder,
          onChange: (event) => onChange(field.key, event.target.value),
        }
        return (
          <label key={field.key} className="bp-profile__field">
            <span>{field.label}</span>
            {group.long ? <textarea rows={3} {...props} /> : <input type="text" {...props} />}
          </label>
        )
      })}
    </div>
  ))
}

export function ListEditor({ list, rows, onChange }) {
  const update = (index, key, value) => onChange(rows.map((row, at) => (at === index ? { ...row, [key]: value } : row)))
  const blank = Object.fromEntries(list.columns.map((column) => [column.key, '']))
  return (
    <fieldset className="bp-profile__list">
      <legend>{list.title}</legend>
      {rows.map((row, index) => (
        <div className="bp-profile__row" key={index} style={{ '--cols': list.columns.length }}>
          {list.columns.map((column) => (
            <input
              key={column.key}
              type="text"
              value={row[column.key] || ''}
              maxLength={column.max}
              placeholder={column.placeholder || column.label}
              aria-label={column.label}
              onChange={(event) => update(index, column.key, event.target.value)}
            />
          ))}
          <button
            type="button"
            className="bp-profile__remove"
            aria-label="Удалить строку"
            title="Удалить строку"
            onClick={() => onChange(rows.filter((_row, at) => at !== index))}
          >
            ×
          </button>
        </div>
      ))}
      {rows.length < LIST_LIMIT && (
        <button type="button" className="btn btn--small" onClick={() => onChange([...rows, blank])}>
          + Строка
        </button>
      )}
    </fieldset>
  )
}
