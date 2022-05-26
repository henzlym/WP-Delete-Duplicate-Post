import apiFetch from '@wordpress/api-fetch';
import { 
    useCallback,
    useEffect,
    useState
} from '@wordpress/element';



export default function Search({ isSearching, setIsSearching, setTerm }) {

    const fetchDuplicates = useCallback( async () => {
        await apiFetch({ path: '/bca/delete-duplicates/v1/search' }).then((results) => {
            // console.log( results );
            setTerm(results);
            setIsSearching( false );
        });
    });

    useEffect(
        () => {
            if (!isSearching) {
                return;
            }
            fetchDuplicates();
        },[isSearching]
    )

    return(
        <button 
            id="start-search" 
            onClick={ () => setIsSearching(true) }
        >
            Search Duplicates
        </button>
    )
}
