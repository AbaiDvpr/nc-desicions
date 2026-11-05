export const NAME = window.data.name;
export const API_DOMAIN = window.data.apiDomain;
export const ENV = process.env.MIX_APP_ENV;
export const ENV_PROD = 'production';
export const IS_PRODUCTION = (ENV ? ENV : ENV_PROD) === ENV_PROD;
export const SENTRY_DSN = 'https://c41cb9cbdfd543678b7f917fc65afeeb@o996083.ingest.sentry.io/4504321702100992';
export const FORM_DATA = window.data.formData;
