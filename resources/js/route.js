import { Ziggy } from './ziggy';
import * as routeImport from 'ziggy-js';

export const route = (name, params = {}, absolute = true, config = Ziggy) => {
  return routeImport.route(name, params, absolute, config);
};
