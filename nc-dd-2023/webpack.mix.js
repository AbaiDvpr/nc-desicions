if (['admin', 'site', 'cabinet', 'all'].includes(process.env.npm_config_application)) {
    require(`${__dirname}/webpack/webpack.${process.env.npm_config_application}.mix.js`);
} else {
    throw new Error('Provide correct --application argument to build command!');
}
