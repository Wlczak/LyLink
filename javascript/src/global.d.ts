interface DocumentEventMap {
    VariablesLoaded: CustomEvent<void>;
}

interface Variables {
    address: string;
    token: string;
}

interface Window {
    Variables?: Variables;
}
