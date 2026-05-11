/* Interfacing admin-body browser process shim. */
(function bootstrapInterfacingBrowserProcess(globalScope) {
  var currentProcess = globalScope.process && typeof globalScope.process === 'object' ? globalScope.process : {};
  var currentEnv = currentProcess.env && typeof currentProcess.env === 'object' ? currentProcess.env : {};

  currentEnv.NODE_ENV = currentEnv.NODE_ENV || 'production';
  currentProcess.env = currentEnv;
  globalScope.process = currentProcess;
  globalScope.globalThis.process = currentProcess;
})(globalThis);
