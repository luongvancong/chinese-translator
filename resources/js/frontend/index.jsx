import {createRoot} from "react-dom/client";
import React from "react";
import {App} from "./app";

if(document.getElementById('app-root')){
    createRoot(document.getElementById('app-root')).render(<App />)
}
