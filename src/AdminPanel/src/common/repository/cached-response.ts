import {VoidFunction} from "../utils/function-utils";
import {inArray} from "../utils/array-utils";

const CACHED_VALUE_KEY = '_cachedValue';

class CacheRegistry {
  private cachedMethods = [];

  registerCachedMethod(method) {
    if (!inArray(method, this.cachedMethods)) {
      this.cachedMethods.push(method);
    }
  }

  clearAll() {
    this.cachedMethods.forEach(method => clearCachedResponse(method));
  }
}

export const cachedResponseRegistry = new CacheRegistry();

export function cachedResponse(expirationPolicy?: (returnValue: any, clearCallback: VoidFunction) => void) {
  return (target: any, propertyName: string, descriptor: TypedPropertyDescriptor<any>) => {
    if (descriptor.value) {
      const originalMethod = descriptor.value;
      let fn = function (...args: any[]) {
        const argumentsHash = getCachedArgumentsHash(args);
        let returnValue = fn[CACHED_VALUE_KEY][argumentsHash];
        if (returnValue === undefined) {
          returnValue = fn[CACHED_VALUE_KEY][argumentsHash] = originalMethod.apply(this, args);
          if (expirationPolicy !== undefined) {
            const clearCallback = () => clearCachedResponse(fn, argumentsHash);
            expirationPolicy(returnValue, clearCallback);
          }
        }
        return returnValue;
      };
      fn[CACHED_VALUE_KEY] = {};
      cachedResponseRegistry.registerCachedMethod(fn);
      descriptor.value = fn;
      return descriptor;
    } else {
      throw "Only put a cachedResponse decorator on a method.";
    }
  };
}

export function forOneMinute(): (returnValue: any, clearCallback: VoidFunction) => void {
  return forSeconds(60);
}

export function forSeconds(seconds: number): (returnValue: any, clearCallback: VoidFunction) => void {
  return (_, clearCallback: VoidFunction) => setTimeout(clearCallback, seconds * 1000);
}

export function untilPromiseCompleted(returnedPromise: Promise<any>, clearCallback: VoidFunction) {
  returnedPromise.finally(clearCallback);
}

export function getCachedArgumentsHash(args: any[]): string {
  return JSON.stringify(args);
}

export function clearCachedResponse(method, argumentsHash: string = undefined) {
  if (method[CACHED_VALUE_KEY]) {
    if (argumentsHash) {
      delete method[CACHED_VALUE_KEY][argumentsHash];
    } else {
      method[CACHED_VALUE_KEY] = {};
    }
  }
}
