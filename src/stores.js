import { writable } from 'svelte/store';

let authToken;
let isLogged = writable(false);

if(typeof window !== "undefined") {
  authToken = writable(localStorage.getItem("authToken") || "");
  authToken.subscribe(val => localStorage.setItem("authToken", val));
} else {
  authToken = writable('');
}

export {authToken, isLogged}