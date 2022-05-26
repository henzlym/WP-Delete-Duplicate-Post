import { 
    useSelect
} from '@wordpress/data';
import { 
    useState,
    useEffect
} from '@wordpress/element';

export default function Loader({ isLoading }) {
    const [loading, setLoading] = useState( isLoading );

    useEffect( () => {
        setLoading(isLoading);
    },[isLoading]);

    console.log('loading', loading);
    return(
        <div className={`bca-loader-container ${loading ? 'active' : ''}`}>
            <h3>Scanning Database for duplicate content ...</h3>
            <div id="bca-loader">
                <div className="lds-ellipsis">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    )
}