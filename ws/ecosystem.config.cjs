module.exports = {
  apps: [
    {
      name: 'classic22-ws',
      cwd: __dirname,
      script: 'server.js',
      exec_mode: 'fork',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        WS_HOST: '127.0.0.1',
        WS_PORT: '9527'
      }
    }
  ]
};

