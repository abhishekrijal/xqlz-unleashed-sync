import { render } from "@wordpress/element";
import Dashboard from "./dashboard";

function App() {
    return (
        <Dashboard />
    )
}

(function () {
    let app = document.getElementById("xqluzSyncAdminPageRoot");

    document.addEventListener("DOMContentLoaded", function () {
        if (null !== app) {
            render(<App />, app);
        }
    });
})();